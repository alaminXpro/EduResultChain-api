<?php

namespace Vanguard\Support\Sidebar;

use Closure;
use Illuminate\Support\Collection;
use Vanguard\User;

class Item
{
    protected ?string $route = null;

    protected ?string $href = null;

    protected ?string $icon = null;

    protected ?string $activePath = null;

    protected string|array|closure|null $permissions = null;

    protected ?Collection $children = null;

    public function __construct(protected string $title)
    {
    }

    /**
     * Factory method to easily create a new Item instance with a given title.
     */
    public static function create($title): Item
    {
        return new self($title);
    }

    /**
     * The route to which the rendered item should point to.
     */
    public function route($route): self
    {
        $this->route = $route;

        return $this;
    }

    /**
     * A URL to be used if there is no named route defined for the navigation item or if it is an external URL.
     * If this attribute is set, it will have higher priority than the $route attribute.
     */
    public function href(string $href): self
    {
        $this->href = $href;

        return $this;
    }

    /**
     * Sidebar navigation icon.
     */
    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * The path which indicates when this navigation item should be marked as active.
     * It can contain wildcard characters.
     *
     * Example:
     *
     * 'users*' (the item will be active whenever a current URL start with "user")
     */
    public function active(string $path): self
    {
        $this->activePath = $path;

        return $this;
    }

    public function getActivePath(): ?string
    {
        return $this->activePath;
    }

    /**
     * If item has secondary navigation links, this method will return all the URL patterns when
     * this navigation item should be expanded.
     */
    public function getExpandedPath(): ?array
    {
        if (! $this->children->count()) {
            return null;
        }

        return $this->children->toBase()->map(function (Item $item) {
            return $item->getActivePath();
        })->toArray();
    }

    /**
     * Returns the "href" attribute (the URL) for the navigation item.
     */
    public function getHref(): ?string
    {
        if ($this->href) {
            return $this->href;
        }

        return $this->route ? route($this->route) : null;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the permissions required for rendering the navigation item.
     */
    public function permissions(string|array|closure $permissions): self
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function getPermissions(): string|array|closure|null
    {
        return $this->permissions;
    }

    /**
     * Checks if item has nested "children" items.
     */
    public function isDropdown(): bool
    {
        return $this->children && $this->children->count();
    }

    /**
     * Get the collection of nested items.
     */
    public function children(): ?Collection
    {
        return $this->children;
    }

    /**
     * Attach an array of children to the item.
     */
    public function addChildren(array $children): self
    {
        if (is_null($this->children)) {
            $this->children = new Collection;
        }

        foreach ($children as $child) {
            $this->children->push($child);
        }

        return $this;
    }

    /**
     * Check if the specified user can view the item.
     */
    public function authorize(User $user): bool
    {
        if ($this->permissions instanceof Closure) {
            return call_user_func($this->permissions, $user);
        }

        foreach ((array) $this->permissions as $permission) {
            if (! $user->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }
}
