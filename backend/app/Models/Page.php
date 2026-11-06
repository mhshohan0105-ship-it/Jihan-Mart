<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'title', 'slug', 'content', 'position', 'meta_title', 'meta_description', 'keywords', 'meta_image_upload_id'])]
class Page extends Model
{
    /** Footer column a page link shows under. */
    public const POSITIONS = [
        'shopping_info' => 'Shopping Information',
        'about' => 'About',
        'service_info' => 'Service Information',
        'category' => 'Category',
        'know_us' => 'Know Us',
    ];

    /** Pages every store starts with; they can be edited but not deleted. */
    public const DEFAULTS = [
        'home' => ['Home Page', 'home'],
        'seller-policy' => ['Seller Policy', 'default'],
        'return-policy' => ['Return Policy', 'default'],
        'support-policy' => ['Support Policy', 'default'],
        'terms' => ['Terms and Conditions', 'default'],
        'privacy-policy' => ['Privacy Policy', 'default'],
    ];

    public function url(): string
    {
        return $this->type === 'home' ? url('/') : url('/page/'.$this->slug);
    }
}
