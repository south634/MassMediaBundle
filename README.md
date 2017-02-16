# MassMediaBundle
A Symfony bundle for creating hashed filenames from uploaded files, and spreading them out across directories.

You can set the hashing algorithm you want to use to create filenames, how many folders deep you want the files to be stored, and how many characters to use per folder name. It uses the leading characters in the hashed filename to create the folder names.

For example, given 'sha1' as your hashing algo, you might get a filename like this:

`3882be53dbfc4a0a4305fba989d224b863fe8cfd.jpg`

If you set folder character length: 2, and folder depth: 2, that file would be stored here:

`/38/82/3882be53dbfc4a0a4305fba989d224b863fe8cfd.jpg`

Setting folder character length: 3, and folder depth: 1, would store the file here:

`/388/3882be53dbfc4a0a4305fba989d224b863fe8cfd.jpg`

This functionality may be useful for those that do not want to have too many files residing in any single directory.

## Setup

Set your configuration in config.yml.

The minimum required settings are given in an example below:
```
# app/config/config.yml
south634_mass_media:
    settings:
        hash_algo: sha1
        upload_dir: media        
        folder_depth: 2
        folder_chars: 2
```

**Required Settings:**

`hash_algo`

The hashing algorithm you want to use for filename creation. Must be an algo present in the array returned by [the PHP hash_algos() function](http://php.net/manual/en/function.hash-algos.php).

`upload_dir`

Name of folder where you want to store the uploads. This folder will be created in your Symfony application's web accessible directory.

`folder_depth`

How many folders deep you want to store the files. Minimum is 0.

`folder_chars` 

How many characters you want to use per folder name. Minimum is 0.

Note: `folder_depth` * `folder_chars` should never be greater than your expected filename length. For example, with `hash_algo: sha1` you'd expect filename hashes 40 characters long. In that case, you should not set `folder_depth: 20` and `folder_chars: 3`, because 20 * 3 > 40. Your filename would not have enough characters to create the folders.

**Optional Settings:**

`web_dir_name`

Name of the web accessible directory in your Symfony application. Defaults to 'web'.

`root_dir`

Absolute path to the root directory of your Symfony application. Defaults to '%kernel.root_dir%'.

How to use
------

### MassMediaManager service

Get the MassMediaManager service in your Controller like so:

```
$mmm = $this->get('south634_mass_media.manager');
```

### Create a filename from UploadedFile (Prevent duplicate filenames)

```
$fileName = $mmm->getFileName($file);
```

This creates a hashed filename from an [UploadedFile object](http://api.symfony.com/2.3/Symfony/Component/HttpFoundation/File/UploadedFile.html).

MassMediaManager uses PHP's hash_file() function to create the filename here. That means that even if you upload two identical files which have different filenames, you should receive the same hashed filename for both of those files.

For example, say you have a cat photo, 'cute-cat.jpg'. You copy that photo elsewhere and rename it, 'kittypic.jpg'. Now, you upload both photos. As the images are still the same, the hash created by hash_file() will be the same them.

This could be useful if you want to cut down on duplicate files being stored on your server. Just be careful to check if a file is owned by any other entities before deleting it.

### Create filename from UploadedFile (Allow duplicate files)

If you want to allow uploads of duplicate files, the `getFileName()` method accepts an optional `$unique` parameter which can be used to do so. For example, you could create a unique hashed filename for each user by adding the User's id to the file like so:

```
$fileName = $mmm->getFileName($file, $user->getId());
```

Using the same example with the 2 identical cat photo files from above, imagine that User 1 uploads 'cute-cat.jpg', while User 2 uploads 'kittypic.jpg'. Even though it's an identical file, their unique user id is added to the hash, which results in a different filename that is unique to each user, and can be stored separately.


### Create a Filename from URL

```
$fileName = $mmm->getFileNameFromUrl($url);
```

If you want to create a filename from a URL instead of an UploadedFile object, use the `getFileNameFromUrl()` method instead. This method also excepts the optional 2nd `$unique` parameter:

```
$fileName = $mmm->getFileNameFromUrl($url, $unique);
```

### Upload a file

```
$mmm->uploadFile($file);
```

Use `uploadFile()` to upload an UploadedFile file object. It will create all the necessary folders for this file's path within the `upload_dir` you set in your config.yml settings. Also, can accept a second optional unique parameter:

```
$mmm->uploadFile($file, $unique);
```


### Upload File from URL

```
$mmm->uploadFileFromUrl($url);
```

Same as `uploadFile()` but for URLs instead of UploadedFile objects. You will need allow_url_fopen enabled to use this. Also, can accept a second optional unique parameter:

```
$mmm->uploadFileFromUrl($url, $unique);
```


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


Twig
------

To get the web path to your file in a Twig template you can use the `mass_media_web_path` filter like so:

```
<img src="{{ asset(product.photo|mass_media_web_path) }}">
```
