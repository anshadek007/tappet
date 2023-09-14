<?php

/*
  |--------------------------------------------------------------------------
  | Here will be listed all CONSTANTS used through the pages
  |--------------------------------------------------------------------------
 */

return [
    /*
      |--------------------------------------------------------------------------
      | App Default version
      |--------------------------------------------------------------------------
      |
      | Array of sales department
      |
     */
    'default_version' => "1.0",
    'DATE_FORMAT' => 'd-m-Y h:i a',
    'DATE_ONLY_NEW' => 'm-d-Y',
    'DATE_TIME_US' => 'm-d-Y h:i A',
    'DATE_TIME_SECOND_US' => 'Y-m-d H:I:S',
    'SUBCATEGORY_COUNT' => '5',
    'DEFAULT_PAGINATION_LIMIT' => 20,
    'ENCRYPT_KEY'=> "e8ffc7e56311679f12b6fc91aa77a5eb",
    'ENCRYPT_IV'=>"1234567891234567",
    'UPLOAD_REL_PATH' => public_path('uploads/'),
    'DEFAULT_USER_IMAGE_ABS' => 'default.png',
    'DEFAULT_PLACEHOLDER_IMAGE' => 'no-image-placeholder.jpg',
    'UPLOAD_ADMINS_FOLDER' => "admins",
    'UPLOAD_USERS_FOLDER' => "users",
    'UPLOAD_CATEGORIES_FOLDER'=> "categories",
    'UPLOAD_COUNTRIES_FOLDER'=> "countries",
    'UPLOAD_PET_TYPES_FOLDER'=> "pet_types",
    'UPLOAD_PETS_FOLDER'=> "pets",
    'UPLOAD_GROUPS_FOLDER'=> "groups",
    'UPLOAD_EVENTS_FOLDER'=> "events",
    'UPLOAD_POSTS_FOLDER'=> "posts",
];
