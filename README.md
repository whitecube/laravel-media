# Laravel Media

Easily optimize and access images stored as attributes on models.

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
<img src="{{ $user->avatar->src('profile-cover') }}" alt="{{ $user->avatar->alt(default: $user->fullname) }}" />
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
<img src="{{ $post->img->withPlaceholder(asset('default.webp'))->src() }}" alt="{{ $post->img->alt() }}" />
```

More on the [`Whitecube\Media\Image` class' capabilities](#displaying-images) below.

> **Note**: Laravel 13 (and earlier) does not allow objects to be assigned to a model's `casts()` array. Mutator attribute methods are currently the only way to go.

## Defining image variant generators

Each variant generator needs to implement the `Whitecube\Media\Generators\Variant` contract, exposing it 3 main methods:
- `key`: the variant's name, which can be used as the image's "default" or specifically requested ;
- `output`: the variant's configuration definition, used to check the variant's expectations ;
- `generate`: the actual method that will be called when a concrete variant needs to be created.

```php
<?php

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

## Displaying images

TODO.

## Roadmap

- Adding a full version of the `DatabaseRepository` as the package's default media repository, enabling extension by media libraries.
- Adding new media types & documents.
- Adding media collections for galleries.
