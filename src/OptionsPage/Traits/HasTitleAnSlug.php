<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Traits;

trait HasTitleAnSlug
{
    /**
     * The title
     *
     * @var string
     */
    public string $title;

    /**
     * The slug
     *
     * @var string
     */
    public string $slug;

    /**
     * The dashicon
     * @var string
     */
    public string $icon;

    /**
     * Get the title
     *
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Get the slug
     *
     * @return string
     */
    public function slug(): string
    {
        return $this->slug;
    }

    /**
     * Get the icon
     *
     * @return string
     */
    public function icon(): string
    {
        return $this->icon;
    }
}
