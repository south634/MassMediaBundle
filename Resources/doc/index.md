You can set the hashing algorithm you want to use to create filenames, how many folders deep you want the files to be stored, and how many characters to use per folder name. It uses the leading characters in the hashed filename for the folder names.

For example, given 'sha1' as your hashing algo, you might get a filename like this:

`3882be53dbfc4a0a4305fba989d224b863fe8cfd.jpg`

If you set the folder character length to 2, and folder depth to 2, that file would be stored here:

`38/82/3882be53dbfc4a0a4305fba989d224b863fe8cfd.jpg`

This functionality may be useful for those that do not want to have too many files residing in any single directory.

## Setup

#### Namespace
Add a namespace to **MassMediaManager.php**.

For example, given a location of:

`src/AppBundle/Util/MassMediaManager.php`

Add namespace:

`namespace AppBundle\Util;`

#### Parameters
You can inject your MassMediaManager settings as an array to its constructor. Probably most convenient is to just put these settings in your parameters file. You can then pass them in as an argument to the service which you'll setup later.

Example settings:

```
# app/config/parameters.yml
parameters:
    mass_media_manager.settings:
        hash_algo: sha1
        folder_depth: 2
        folder_chars: 2
        upload_dir: media
        web_dir_name: web
        root_dir: '%kernel.root_dir%'
```
		
		
`hash_algo`

The hashing algorithm you want to use for filename creation. Must be an [algorithm supported by PHP](http://php.net/manual/en/function.hash-algos.php).

`folder_depth`

How many folders deep you want to store the files.

`folder_chars` 

How many characters you want to use per folder name.

`upload_dir`

Name of folder where you want to store the uploads.

`web_dir_name`

Name of your web directory. Usually 'web' in Symfony but might be 'html' depending on your setup.

`root_dir`

Absolute path to the root directory of your Symfony application.

**IMPORTANT**: `folder_depth` * `folder_chars` should never be higher than your expected filename length. For example, with `hash_algo: sha1` you'd expect filename hashes 40 characters long. In that case, you should not set `folder_depth: 20` and `folder_chars: 3`, because 20 * 3 > 40. Your filename would not have enough characters to create the folders.

### Service
Add MassMediaManager to your services.

Given the example location of: *src/AppBundle/Util/MassMediaManager.php*

You'd add this:

```
# app/config/services.yml
services:
    mass_media_manager:
        class: AppBundle\Util\MassMediaManager
        arguments: ["%mass_media_manager.settings%"]
```

Notice that you pass in your MassMediaManager settings from parameters.yml as an argument here.

### Twig
If you want to use MassMediaManager in Twig templates for retrieving web paths to files, you can add it to your Twig global variables:

```
# app/config/config.yml
twig:
    globals:
        media: "@mass_media_manager"
```
		
Then, say you have a `Product` entity with a filename stored in its `photo` property. You could display its image in your template like so:

```
<img src="{{ asset(media.getWebPath(product.photo)) }}">
```

Naming the global Twig variable `media` is not a requirement. You can name it whatever you like. 


Brief explanation of methods for MassMediaManager $mmm
------

### Create a Filename (Prevent duplicate files)

```
$fileName = $mmm->getFileName($file);
```

Creates a hashed filename from an [UploadedFile object](http://api.symfony.com/2.3/Symfony/Component/HttpFoundation/File/UploadedFile.html).

Note: MassMediaManager uses PHP's hash_file() function to create the filename here. That means that if you upload two identical files which have different filenames, you will receive the same hashed filename for both files.

For example, say you have a cat photo, 'cute-cat.jpg'. You copy that photo elsewhere and rename it, 'kittypic.jpg'. Now, you upload both photos. As the images are still the same, the hash created by hash_file() will be the same for both photos.

This could be useful if you want to cut down on duplicate files being stored on your server. Just be careful to check if a file is owned by any other entities before deleting it.

### Create a Filename (Allow duplicate files)

If you want to allow uploads of duplicate files, the `getFileName()` method accepts an optional `$unique` parameter which can be used to do so. For example, you could create a unique hashed filename for each user by adding the User's id to the file like so:

```
$fileName = $mmm->getFileName($file, $user->getId());
```

Using the same example with the 2 identical cat photo files from above, imagine that User 1 uploads 'cute-cat.jpg', while User 2 uploads 'kittypic.jpg'. Even though it's an identical file, their unique user id is added to the hash, which results in a different filename that is unique to each user, and can be stored separately.


### Create a Filename from URL

```
$fileName = $mmm->getFileNameFromUrl($url, $unique = null);
```

If you want to create a filename from a URL instead of an UploadedFile object, use the `getFileNameFromUrl()` method instead. This method also excepts the optional `$unique` parameter.

### Upload File

```
$mmm->uploadFile($file, $fileName);
```

Use `uploadFile()` to upload an UploadedFile file object to its location based on the `$fileName` given. Will create necessary folders along the way.

### Upload File from URL

```
$mmm->uploadFileFromUrl($url, $fileName);
```

Same as `uploadFile()` but for URLs instead of UploadedFile objects.


### Remove File

```
$mmm->removeFile($fileName);
```

Removes a file, and any empty subdirectories created for that file. Just pass in the filename.

Alternatively, if you want to remove a file, but keep any empty subdirectories created for it, use `false` as the second parameter to this method like so:

```
$mmm->removeFile($fileName, false);
```

### Get Web Path

```
$mmm->getWebPath($fileName);
```

Returns the web path of a file.

### More documentation to come...

