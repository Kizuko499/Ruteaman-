<?php
return [
 'avatar_upload_requires_consent'=>env('AVATAR_UPLOAD_REQUIRES_CONSENT',true),
 // Age is not collected. Conservatively apply consent to every upload.
 'blocked_names'=>array_filter(array_map('trim',explode(',',env('ACCOUNT_BLOCKED_NAMES','admin,administrator,moderator,pengguna dihapus,anjing,bangsat,kontol')))),
 'help_contact'=>env('SCHOOL_HELP_CONTACT'),
 'school_contacts'=>json_decode(env('SCHOOL_HELP_CONTACTS','{}'),true) ?: [],
];
