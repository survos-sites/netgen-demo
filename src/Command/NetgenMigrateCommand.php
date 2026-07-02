<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Create (or reset) the Netgen Layouts schema on the dedicated nglayouts
 * DBAL connection.
 *
 * The schema DDL is Netgen's own Postgres file:
 *   vendor/netgen/layouts-core/resources/data/schema.pgsql.sql
 *
 * This command uses the DBAL connection directly — no psql binary required.
 *
 * Typical usage:
 *   bin/console app:netgen:migrate                   # create schema (idempotent: IF NOT EXISTS)
 *   bin/console app:netgen:migrate --reset           # drop tables then recreate
 */
#[AsCommand('app:netgen:migrate', 'Create or reset the Netgen Layouts schema on the nglayouts DBAL connection')]
final class NetgenMigrateCommand
{
    /**
     * Ordered for safe CASCADE-free DROP: dependents before dependencies.
     */
    private const DROP_ORDER = [
        'nglayouts_rule_condition_rule_group',
        'nglayouts_rule_condition_rule',
        'nglayouts_rule_condition',
        'nglayouts_rule_target',
        'nglayouts_rule_data',
        'nglayouts_rule',
        'nglayouts_rule_group_data',
        'nglayouts_rule_group',
        'nglayouts_collection_slot',
        'nglayouts_block_collection',
        'nglayouts_collection_query_translation',
        'nglayouts_collection_query',
        'nglayouts_collection_item',
        'nglayouts_collection_translation',
        'nglayouts_collection',
        'nglayouts_zone',
        'nglayouts_block_translation',
        'nglayouts_block',
        'nglayouts_layout_translation',
        'nglayouts_layout',
        'nglayouts_role_policy',
        'nglayouts_role',
    ];

    private const SCHEMA_FILE = 'vendor/netgen/layouts-core/resources/data/schema.pgsql.sql';

    public function __construct(
        // Injected by name — resolves to the 'nglayouts' DBAL connection.
        private readonly Connection $nglayoutsConnection,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Option('drop all nglayouts_* tables and sequences before recreating')]
        bool $reset = false,
    ): int {
        $schemaFile = self::SCHEMA_FILE;
        if (!file_exists($schemaFile)) {
            $io->error(sprintf('Schema file not found: %s', $schemaFile));
            return Command::FAILURE;
        }

        if ($reset) {
            $io->section('Dropping existing nglayouts tables and sequences');
            $this->dropTablesAndSequences($io);
        }

        $io->section(sprintf('Applying Netgen schema from %s', $schemaFile));

        $sql = file_get_contents($schemaFile);
        if ($sql === false) {
            $io->error('Failed to read schema file.');
            return Command::FAILURE;
        }

        $statements = $this->splitStatements($sql);
        $io->writeln(sprintf('  <info>%d</info> statements to execute', count($statements)));

        $conn = $this->nglayoutsConnection;
        $conn->beginTransaction();

        try {
            foreach ($statements as $i => $stmt) {
                $conn->executeStatement($stmt);
                if (($i + 1) % 10 === 0) {
                    $io->writeln(sprintf('  %d/%d…', $i + 1, count($statements)));
                }
            }
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            $io->error(sprintf('Statement failed: %s', $e->getMessage()));
            if ($io->isVerbose()) {
                $io->writeln('<comment>Failed SQL:</comment>');
                $io->writeln($statements[$i ?? 0] ?? '(unknown)');
            }
            return Command::FAILURE;
        }

        $io->success(sprintf('Netgen schema applied (%d statements).', count($statements)));
        return Command::SUCCESS;
    }

    private function dropTablesAndSequences(SymfonyStyle $io): void
    {
        $conn = $this->nglayoutsConnection;

        foreach (self::DROP_ORDER as $table) {
            $conn->executeStatement(sprintf('DROP TABLE IF EXISTS %s CASCADE', $table));
            $io->writeln(sprintf('  Dropped table <comment>%s</comment>', $table));
        }

        // Drop sequences (Netgen uses explicit sequences for Postgres)
        $sequences = $conn->executeQuery(
            "SELECT sequence_name FROM information_schema.sequences
             WHERE sequence_schema = 'public' AND sequence_name LIKE 'nglayouts_%'"
        )->fetchFirstColumn();

        foreach ($sequences as $seq) {
            $conn->executeStatement(sprintf('DROP SEQUENCE IF EXISTS %s CASCADE', $seq));
            $io->writeln(sprintf('  Dropped sequence <comment>%s</comment>', $seq));
        }
    }

    /**
     * Split a SQL file into individual statements, handling:
     * - Multi-line statements (CREATE TABLE, etc.)
     * - Statements ending with ; as delimiter
     * - Blank lines and comment-only blocks (-- lines)
     * - Dollar-quoted strings (not present in Netgen's schema, but safe)
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer     = '';

        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);

            // Skip pure comment lines and blank lines when buffer is empty
            if ($buffer === '' && ($trimmed === '' || str_starts_with($trimmed, '--'))) {
                continue;
            }

            $buffer .= $line . "\n";

            if (str_ends_with($trimmed, ';')) {
                $stmt = trim($buffer);
                if ($stmt !== '' && $stmt !== ';') {
                    $statements[] = $stmt;
                }
                $buffer = '';
            }
        }

        // Flush any trailing statement without trailing semicolon
        $stmt = trim($buffer);
        if ($stmt !== '' && $stmt !== ';') {
            $statements[] = $stmt;
        }

        return $statements;
    }
}
