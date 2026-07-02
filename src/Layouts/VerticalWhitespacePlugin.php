<?php

namespace App\Layouts;

use Netgen\Layouts\Block\BlockDefinition\BlockDefinitionHandlerInterface;
use Netgen\Layouts\Block\BlockDefinition\Handler\Plugin;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Netgen\Layouts\Parameters\ParameterType;

class VerticalWhitespacePlugin extends Plugin
{
    public static function getExtendedHandlers(): iterable
    {
        yield BlockDefinitionHandlerInterface::class;
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $builder->add(
            'vertical_whitespace:enabled',
            ParameterType\Compound\BooleanType::class,
            [
                'default_value' => false,
                'label' => 'Enable Vertical Whitespace?',
                'groups' => [self::GROUP_DESIGN],
            ],
        );

        $builder->get('vertical_whitespace:enabled')->add(
            'vertical_whitespace:top',
            ParameterType\ChoiceType::class,
            [
                'default_value' => 'medium',
                'label' => 'Top Spacing',
                'options' => [
                    'None' => 'none',
                    'Small' => 'small',
                    'Medium' => 'medium',
                    'Large' => 'large',
                ],
                'groups' => [self::GROUP_DESIGN],
            ],
        );

        $builder->get('vertical_whitespace:enabled')->add(
            'vertical_whitespace:bottom',
            ParameterType\ChoiceType::class,
            [
                'default_value' => 'medium',
                'label' => 'Bottom Spacing',
                'options' => [
                    'None' => 'none',
                    'Small' => 'small',
                    'Medium' => 'medium',
                    'Large' => 'large',
                ],
                'groups' => [self::GROUP_DESIGN],
            ],
        );
    }
}
