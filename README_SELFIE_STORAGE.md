Selfie storage setup

To serve uploaded selfies, ensure the public disk is linked:

1. Run `php artisan storage:link` to create `public/storage` symlink.
2. Verify `filesystems.php` has `public` disk using `storage/app/public` with `url` => env('APP_URL').'/storage'.
3. Uploaded files are stored under `storage/app/public/selfies` and accessible via `Storage::url($path)`.