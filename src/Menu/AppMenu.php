<?php

declare(strict_types=1);

namespace App\Menu;

use Survos\TablerBundle\Event\MenuEvent;
use Survos\TablerBundle\Traits\KnpMenuHelperInterface;
use Survos\TablerBundle\Traits\KnpMenuHelperTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AppMenu implements KnpMenuHelperInterface
{
    use KnpMenuHelperTrait;

    public function __construct(
        private readonly ?AuthorizationCheckerInterface $authorizationChecker = null,
        private readonly ?Security $security = null,
    ) {
    }

    #[AsEventListener(event: MenuEvent::AUTH)]
    public function onAuthMenu(MenuEvent $event): void
    {
        $menu = $event->getMenu();
        $user = $this->security?->getUser();

        if ($user === null) {
            $this->add($menu, 'app_login', label: 'Login', icon: 'login');

            return;
        }

        $this->add($menu, 'app_homepage', label: $user->getUserIdentifier(), icon: 'user');
        $this->add($menu, 'app_logout', label: 'Logout', icon: 'logout');
    }

    #[AsEventListener(event: MenuEvent::NAVBAR_MENU)]
    public function navbarMenu(MenuEvent $event): void
    {
        $menu = $event->getMenu();

        $this->add($menu, 'app_homepage', label: 'Home');
        $this->add($menu, 'app_recipes', label: 'Recipes');

        if ($this->authorizationChecker?->isGranted('ROLE_NGLAYOUTS_ADMIN')) {
            $this->add($menu, 'nglayouts_admin', label: 'Layouts Admin');
        }

        if ($this->authorizationChecker?->isGranted('ROLE_ADMIN')) {
            $this->add($menu, 'admin', label: 'Recipes Admin');
        }
    }
}
