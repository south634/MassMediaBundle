# MassMediaBundle
A Symfony bundle for creating hashed filenames from uploaded files, and spreading them out across directories.

You can set the hashing algorithm you want to use to create filenames, how many folders deep you want the files to be stored, and how many characters to use per folder name. It uses the leading characters in the hashed filename to create the folder names.

For example, given `sha1` as your hashing algo, you might get a filename like this:

`3882be53dbfc4a0a4305fba989d224b863fe8cfd.jpg`

If you set folder character length: 2, and folder depth: 2, that file would be stored here:

`/38/82/3882be53dbfc4a0a4305fba989d224b863fe8cfd.jpg`

Setting folder character length: 3, and folder depth: 1, would store the file here:

`/388/3882be53dbfc4a0a4305fba989d224b863fe8cfd.jpg`

This functionality may be useful for those that do not want to have too many files residing in any single directory.