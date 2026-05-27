# Laravel Media

Easily optimize and access images stored as attributes on Eloquent models.

```php
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Whitecube\Media\Attributes\Image;

class User extends Model
{
    protected function avatar(): Attribute
    {
        return Image::attribute($this, 'avatar')
            ->variants([
                \App\Media\Images\SquareIcon::class,
                \App\Media\Images\SquareRegular::class,
                \App\Media\Images\ProfileCover::class,
                \App\Media\Images\SocialShare::class,
            ])
            ->default('square-regular')
            ->disk('public')
            ->directory('users');
    }
}
```
```blade
<img
    src="{{ $user->avatar->src('profile-cover') }}"
    alt="{{ $user->avatar->alt(fallback: $user->fullname) }}"
>
```

## Table of contents

1. [Installation](#installation)
2. [Defining image attributes](#defining-image-attributes)
3. [Defining image variant generators](#defining-image-variant-generators)
4. [Displaying images](#displaying-images)
5. [Roadmap](#roadmap)

## Installation

```bash
composer require whitecube/laravel-media
```

This package will auto-register its service provider.

> **Note**: Make sure your configured filesystem disk can generate public URLs when using images in Blade views. For Laravel's public disk, this usually means running `php artisan storage:link`.

## Defining image attributes

Getting started with `laravel-media` is quite simple as it mostly relies on Laravel's Mutators & Casting principles. All you'll have to do is define a mutator attribute representing the image that needs to be handled on the model:

```php
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Whitecube\Media\Attributes\Image;

class Post extends Model
{
    protected function img(): Attribute
    {
        return Image::attribute($this, 'img');
    }
}
```

This will return a `Whitecube\Media\Image` instance when accessing the model's attribute, providing useful methods for a proper display of the image:

```blade
<img src="{{ $post->img->src() }}" alt="{{ $post->img->alt() }}" />
```

More on the [`Whitecube\Media\Image` class’s capabilities](#displaying-images) below.

> **Note**: Laravel 13 (and earlier) does not allow objects to be assigned to a model's `casts()` array. Mutator attribute methods are currently the only way to go.

### Configuring storage

By default, the package uses your application's default filesystem disk. You can change the disk and directory for each image attribute:

```php
protected function img(): Attribute
{
    return Image::attribute($this, 'img')
        ->disk('public')
        ->directory('posts');
}
```

The model attribute will store a key representing the image. When using the filesystem repository, this key usually matches the filename or relative path inside the configured directory.

```php
$post->img = 'cover.webp';
$post->save();
```

When the attribute is configured with `->directory('posts')`, assigning `posts/cover.webp` will automatically be normalized to `cover.webp`.

### Defining variants

Variants are generated from the original file after the model is saved. You can define as many as needed in order to optimize the display, weight and format of the image for all situations.

```php
protected function img(): Attribute
{
    return Image::attribute($this, 'img')
        ->variants([
            \App\Media\Images\PostThumbnail::class,
            \App\Media\Images\PostCover::class,
        ])
        ->default('post-thumbnail')
        ->disk('public')
        ->directory('posts');
}
```

You can also register variants one by one:

```php
return Image::attribute($this, 'img')
    ->variant(\App\Media\Images\PostThumbnail::class)
    ->variant(\App\Media\Images\PostCover::class);
```

Variant generation will be automatically queued after the model is saved. Make sure your queue worker is configured and running when assigning or updating images (`php artisan queue:work`).

### Placeholders

A placeholder can be defined on the attribute:

```php
protected function img(): Attribute
{
    return Image::attribute($this, 'img')
        ->placeholder(asset('images/placeholder.webp'));
}
```

Or directly when displaying the image:

```blade
<img src="{{ $post->img->withPlaceholder(asset('images/placeholder.webp'))->src() }}" ...>
```

The placeholder image will be used for `NULL` values and when the original file is not found.

### Custom getters and setters

You may pass custom getter and setter callbacks to `Image::attribute()` when the stored value needs to be adapted before the package handles it:

```php
protected function img(): Attribute
{
    return Image::attribute(
        model: $this,
        attribute: 'img',
        get: fn ($value) => $value,
        set: fn ($value) => $value,
    );
}
```

### Custom repositories

The default repository reads files from the configured filesystem disk and does not rely on extra tables in your database. However, in some cases, for instance when building media libraries, you would need to store more information about uploaded and generated media. That is when a database-backed media repository becomes useful:

```php
protected function img(): Attribute
{
    return Image::attribute($this, 'img')
        ->repository(\App\Media\DatabaseMediaRepository::class);
}
```

A repository must implement `Whitecube\Media\Repositories\MediaRepository`.

> **Note**: This package is still a work in progress. A default `DatabaseRepository` will be added in a future version.

## Defining image variant generators

Each variant generator needs to implement the `Whitecube\Media\Generators\Variant` contract, exposing three main methods:

- `key`: the variant's name, which can be used as the image's "default" or specifically requested;
- `output`: the variant's configuration definition, used to check the variant's expectations;
- `generate`: the actual method that will be called when a concrete variant needs to be created.

```php
namespace App\Media\Images;

use Whitecube\Media\MediaFile;
use Whitecube\Media\Generators\Variant;
use Whitecube\Media\Generators\Output;
use Whitecube\Media\Generators\Enums\Format;
use Intervention\Image\Laravel\Facades\Image;

class SquareRegular implements Variant
{
    public function key(): string
    {
        return 'square-regular';
    }

    public function output(): Output
    {
        return Output::make(Format::Webp)
            ->suffix($this->key())
            ->fit(width: 512, height: 512);
    }

    public function generate(Output $output): MediaFile
    {
        $image = $output->resize->apply(
            image: Image::decode($output->original->fullPath())
        );

        return $output->store($image, quality: 80);
    }
}
```

In this example, we're using `intervention/image-laravel` for our image manipulations, which has been seamlessly integrated into `whitecube/laravel-media`. You can, however, use any other library inside the `generate` method, as long as it returns a valid `Whitecube\Media\MediaFile` instance.

### Output helpers

The `Output` object provides a few helpers to keep variant filenames and transformations predictable:

```php
return Output::make(Format::Webp)
    ->prefix('generated')
    ->suffix($this->key())
    ->fit(width: 1200, height: 630)
    ->useUniqueFilename();
```

Available helpers include:

- `prefix()` and `suffix()` to customize generated filenames;
- `fit()` to resize and crop an image to the given dimensions;
- `useUniqueFilename()` to generate a random filename;
- `disk()` and `directory()` to override where the variant should be stored;
- `store()` to write the generated file and return a `MediaFile`. It currently only supports `intervention/image-laravel` image objects.

The package currently ships with WebP output support, but other common formats will be added (feel free to open a PR).

## Displaying images

Accessing an image attribute returns a `Whitecube\Media\Image` instance.

```blade
<img src="{{ $post->img->src() }}" alt="{{ $post->img->alt() }}">
```

### Displaying a specific variant

Use the variant key to request a generated variant:

```blade
<img src="{{ $post->img->src('square-regular') }}" alt="{{ $post->img->alt(fallback: $post->title) }}">
```

If the requested variant does not exist yet, `src()` falls back to the original file, then to the configured placeholder (if defined).

### Using the default variant

When a default variant is configured on the attribute, calling `src()` without arguments will use it automatically:

```php
protected function img(): Attribute
{
    return Image::attribute($this, 'img')
        ->variants([
            \App\Media\Images\PostCover::class,
        ])
        ->default('post-cover');
}
```

```blade
<img src="{{ $post->img->src() }}" alt="{{ $post->img->alt(fallback: $post->title) }}">
```

### Checking if an image exists

```blade
@if($post->img->isNotEmpty())
    <img src="{{ $post->img->src() }}" alt="{{ $post->img->alt(fallback: $post->title) }}">
@endif
```

You can also use `isEmpty()`:

```blade
@if($post->img->isEmpty())
    <img src="{{ asset('images/placeholder.webp') }}" alt="">
@endif
```

### Accessing files directly

You can retrieve a concrete `MediaFile` instance for the original file or for a variant:

```php
$original = $post->img->variant('original');
$cover = $post->img->variant('post-cover');

$url = $cover?->url();
$path = $cover?->fullPath();
```

### Casting to string

The image object can be rendered as a string. It will output the same value as `src()`:

```blade
<img src="{{ $post->img }}" alt="">
```

### About `alt`, `srcset` and `sizes`

The `alt()` method currently returns the given fallback. In a future version, it is intended to look for a default value in the configured `MediaRepository`.

```blade
<img src="{{ $post->img->src() }}" alt="{{ $post->img->alt(fallback: $post->title) }}">
```

`srcset()` and `sizes()` are reserved for future responsive image support and currently return `null`.

## Roadmap

- Adding a full version of the `DatabaseRepository` as the package's default media repository, enabling extension by media libraries.
- Adding new media types & documents.
- Adding media collections for galleries.
- Adding responsive image helpers such as `srcset()` and `sizes()`.
