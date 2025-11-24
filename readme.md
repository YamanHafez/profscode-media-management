## What Is Profscode Media Management?

This package provides media/file management for your Laravel models.
It stores files in a model-based directory structure, generates conversions (thumbnails/webp), and provides convenient URL access.

---

## Installation

```
composer require profscode/media-management
```

### 1. Use Trait in Your Model

```php
use Profscode\MediaManagement\MediaManagement;

class User extends Model
{
    use MediaManagement;
}
```

---

## ProfscodeMedia Model Structure

This model keeps metadata about uploaded media files.

### Fields

| Field         | Description                               |
| ------------- | ----------------------------------------- |
| model_id      | Related model ID                          |
| model_type    | Model class name                          |
| collection    | Collection name (avatar, gallery, etc.)   |
| original_name | Original uploaded filename                |
| name          | Stored filename                           |
| mime_type     | MIME type                                 |
| disk          | Laravel storage disk                      |
| size          | File size                                 |
| conversions   | JSON containing thumbnails and webp paths |

---

## addMediaFromRequest Usage

```php
$user = User::find(1);
$user->addMediaFromRequest("profile_picture", "avatars");
```

Requires:

```
<input type="file" name="profile_picture">
```

### Optional conversion structure:

```php
[
    "admin_panel" => [
        "width" => 100,
        "height" => 100,
        "webp" => true
    ]
]
```

---

## addMediaFromUrl Usage

```php
$user->addMediaFromUrl("https://domain.com/image.jpg", "gallery");
```

or

```php
$conversions = [
    "admin_panel" => [
        "width" => 100,
        "height" => 100,
        "webp" => true
    ]
]
$user->addMediaFromUrl("https://domain.com/image.jpg", "gallery", $conversions);
```

---

## Retrieving Media (Relation)

```php
$user->getMedia();
```

retrive Media as Collection

---

## getUrl Usage

### Original URL:

```php
$media->getFirstMediaUrl($collection = "default");
```

### Thumbnail or WebP:

```php
$media->getFirstMediaUrl($collection , 'admin_panel');
```

---

## getFirstMediaUrl Usage

```php
$user->getFirstMediaUrl("avatars", "admin_panel");
```

---

## Storage Structure

```
storage/app/public/media/{ModelType}/{ModelId}/{collection}/{filename.ext}
```

---

## License

MIT © Profscode

---

## Support

For issues, please open a GitHub Issue.
