# Upload storage protection

BioHop rejects uploads with HTTP **507**, code `storage_unavailable` and `Retry-After: 60` when free disk space is low or unavailable. Avatar, background, block image, thumbnail, icon, QR-logo and inline base64 QR uploads all pass through the same guard, including avatar uploads embedded in a page update. Text-only edits and existing public pages remain available.

The default reserve is the greater of **1024 MiB** and **5%** of disk capacity. `BIO_UPLOAD_MIN_FREE_MB` and `BIO_UPLOAD_MIN_FREE_PERCENT` tune it. Checks cover application storage, PHP temporary storage and the local public upload filesystem. Upload processing uses a local filesystem lock between PHP workers and reserves headroom for three copies of the request. Other services can still consume the same disk; this is not a cross-application filesystem quota. For remote public disks, local staging/storage is measured, not remote bucket capacity.

The editor checks `/bio/upload-status` without caching every 30 seconds, shows a warning and disables file inputs when uploads are paused. Direct requests are checked independently. Uploads recover automatically after capacity returns. PHP/proxy request-body buffering occurs before Laravel's guard: configure request-size limits and temporary paths consistently with the upload filesystem.

Recovery: inspect disk capacity, remove unused/expired media using existing maintenance tools, check log rotation or expand storage. No active profile or upload is automatically deleted in response to low capacity.
