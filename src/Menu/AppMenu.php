<?php

declare(strict_types=1);

namespace App\Menu;

use App\Repository\AlbumRepository;
use App\Repository\RecipeRepository;
use Survos\TablerBundle\Event\MenuEvent;
use Survos\TablerBundle\Traits\KnpMenuHelperInterface;
use Survos\TablerBundle\Traits\KnpMenuHelperTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AppMenu implements KnpMenuHelperInterface
{
    use KnpMenuHelperTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RecipeRepository $recipeRepository,
        private readonly AlbumRepository $albumRepository,
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
        $this->add($menu, 'app_widget_demo', label: 'Widget Demo');
        $this->add($menu, 'app_albums', label: 'Albums');

        if ($this->authorizationChecker?->isGranted('ROLE_NGLAYOUTS_ADMIN')) {
            $this->add($menu, 'nglayouts_admin', label: 'Layouts Admin');
        }

        if ($this->authorizationChecker?->isGranted('ROLE_ADMIN')) {
            $this->add($menu, 'admin', label: 'Recipes Admin');
        }
    }

    #[AsEventListener(event: MenuEvent::BREADCRUMB)]
    public function breadcrumbMenu(MenuEvent $event): void
    {
        $menu = $event->getMenu();
        $request = $this->requestStack->getCurrentRequest();
        $route = $request?->attributes->get('_route');

        if ($route === null || $route === 'app_homepage') {
            return;
        }

        $this->add($menu, 'app_homepage', label: 'Home');

        if ($route === 'app_recipes') {
            $this->add($menu, 'app_recipes', label: 'Recipes');
        }

        if ($route === 'app_recipes_show') {
            $this->add($menu, 'app_recipes', label: 'Recipes');

            $slug = $request?->attributes->get('slug');
            $recipe = $slug !== null ? $this->recipeRepository->findOneBy(['slug' => $slug]) : null;
            if ($recipe !== null) {
                $this->add($menu, 'app_recipes_show', ['slug' => $recipe->getSlug()], label: $recipe->getName(), translationDomain: false);
            }
        }

        if ($route === 'app_widget_demo') {
            $this->add($menu, 'app_widget_demo', label: 'Widget Demo');
        }

        if ($route === 'app_recipes_by_tag') {
            $tag = $request?->attributes->get('tag');
            $this->add($menu, 'app_recipes_by_tag', ['tag' => $tag], label: ucfirst((string) $tag), translationDomain: false);
        }

        if ($route === 'app_albums') {
            $this->add($menu, 'app_albums', label: 'Albums');
        }

        if ($route === 'app_albums_show' || $route === 'app_story') {
            $this->add($menu, 'app_albums', label: 'Albums');

            $slug = $request?->attributes->get('slug') ?? $request?->attributes->get('album');
            $album = $slug !== null ? $this->albumRepository->findOneBy(['slug' => $slug]) : null;
            if ($album !== null) {
                $routeName = $route === 'app_story' ? 'app_story' : 'app_albums_show';
                $routeParam = $route === 'app_story' ? 'album' : 'slug';
                $this->add($menu, $routeName, [$routeParam => $album->getSlug()], label: $album->getName(), translationDomain: false);
            }
        }
    }
}
