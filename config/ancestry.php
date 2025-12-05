<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
|--------------------------------------------------------------------------
| Ancestry Ancestor Configuration
|--------------------------------------------------------------------------
|
| This file defines the configuration for Ancestry, a Laravel package that
| provides closure table ancestors for Eloquent models. The closure table
| pattern enables O(1) queries for ancestors and descendants without recursion
| limits, supporting deeply nested relationships like organizational charts,
| sales ancestors, and category trees.
|
*/

use Cline\Ancestry\Database\Ancestor;

return [
    /*
    |--------------------------------------------------------------------------
    | Primary Key Type
    |--------------------------------------------------------------------------
    |
    | This option controls the type of primary key used in Ancestry's database
    | tables. You may use traditional auto-incrementing integers or choose
    | ULIDs or UUIDs for distributed systems or enhanced privacy.
    |
    | Supported: "id", "ulid", "uuid"
    |
    */

    'primary_key_type' => env('ANCESTRY_PRIMARY_KEY_TYPE', 'id'),

    /*
    |--------------------------------------------------------------------------
    | Ancestor Morph Type
    |--------------------------------------------------------------------------
    |
    | This option controls the type of polymorphic relationship columns used
    | for ancestor relationships in Ancestry's database tables. This determines
    | how ancestors are stored in the closure table.
    |
    | Supported: "morph", "uuidMorph", "ulidMorph"
    |
    */

    'ancestor_morph_type' => env('ANCESTRY_ANCESTOR_MORPH_TYPE', 'string'),

    /*
    |--------------------------------------------------------------------------
    | Descendant Morph Type
    |--------------------------------------------------------------------------
    |
    | This option controls the type of polymorphic relationship columns used
    | for descendant relationships in Ancestry's database tables. This determines
    | how descendants are stored in the closure table.
    |
    | Supported: "morph", "uuidMorph", "ulidMorph"
    |
    */

    'descendant_morph_type' => env('ANCESTRY_DESCENDANT_MORPH_TYPE', 'string'),

    /*
    |--------------------------------------------------------------------------
    | Maximum Ancestor Depth
    |--------------------------------------------------------------------------
    |
    | This option controls the maximum depth allowed for ancestors. This
    | prevents infinite recursion and ensures reasonable performance. A depth
    | of 10 supports most organizational structures whilst preventing abuse.
    | Set to null for unlimited depth (not recommended for production).
    |
    */

    'max_depth' => env('ANCESTRY_MAX_DEPTH', 10),

    /*
    |--------------------------------------------------------------------------
    | Eloquent Models
    |--------------------------------------------------------------------------
    |
    | Ancestry needs to know which Eloquent model should be used to interact
    | with the ancestors database table. You may extend this model with
    | your own implementation whilst ensuring it extends the base class
    | provided by Ancestry.
    |
    */

    'models' => [
        /*
        |--------------------------------------------------------------------------
        | Ancestor Model
        |--------------------------------------------------------------------------
        |
        | This model is used to retrieve hierarchy entries from the database.
        | The model you specify must extend the `Cline\Ancestry\Database\Ancestor`
        | class. This allows you to customise the hierarchy model behaviour whilst
        | maintaining compatibility with Ancestry's internal operations.
        |
        */

        'ancestor' => Ancestor::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Name
    |--------------------------------------------------------------------------
    |
    | Ancestry needs to know which table name should be used to store your
    | hierarchy relationships. This table name is used by both the migration
    | and the Eloquent model.
    |
    */

    'table_name' => env('ANCESTRY_TABLE', 'ancestors'),

    /*
    |--------------------------------------------------------------------------
    | Polymorphic Key Mapping
    |--------------------------------------------------------------------------
    |
    | This option allows you to specify which column should be used as the
    | foreign key for each model in polymorphic relationships. This is
    | particularly useful when different models in your application use
    | different primary key column names, which is common in legacy systems
    | or when using ULIDs and UUIDs alongside traditional auto-incrementing
    | integer keys.
    |
    | For example, if your User model uses 'id' but your Organization model
    | uses 'ulid', you can map each model to its appropriate key column here.
    | Ancestry will then use the correct column when storing foreign keys.
    |
    | Note: You may only configure either 'morphKeyMap' or 'enforceMorphKeyMap',
    | not both. Choose the non-enforced variant if you want to allow models
    | without explicit mappings to use their default primary key.
    |
    */

    'morphKeyMap' => [
        // App\Models\User::class => 'id',
        // App\Models\Organization::class => 'ulid',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enforced Polymorphic Key Mapping
    |--------------------------------------------------------------------------
    |
    | This option works identically to 'morphKeyMap' above, but enables strict
    | enforcement of your key mappings. When configured, any model referenced
    | in a polymorphic relationship without an explicit mapping defined here
    | will throw a MorphKeyViolationException.
    |
    | This enforcement is useful in production environments where you want to
    | ensure all models participating in polymorphic relationships have been
    | explicitly configured, preventing potential bugs from unmapped models.
    |
    | Note: Only configure either 'morphKeyMap' or 'enforceMorphKeyMap'. Using
    | both simultaneously is not supported. Choose this enforced variant when
    | you want strict type safety for your polymorphic relationships.
    |
    */

    'enforceMorphKeyMap' => [
        // App\Models\User::class => 'id',
        // App\Models\Organization::class => 'ulid',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ancestor Types
    |--------------------------------------------------------------------------
    |
    | Here you may define the hierarchy types available in your application.
    | Each type represents a different kind of hierarchical relationship
    | (e.g., seller ancestors, category trees, organizational charts).
    |
    | You may define types as a simple array of strings, or as an enum class
    | that implements the AncestorTypeContract interface. Using an enum
    | provides type safety and IDE autocompletion.
    |
    | Simple array: ['seller', 'reseller', 'organization']
    | Enum class: App\Enums\AncestorType::class
    |
    */

    'types' => [
        // 'seller',
        // 'reseller',
        // 'organization',
    ],

    /*
    |--------------------------------------------------------------------------
    | Type Enum Class
    |--------------------------------------------------------------------------
    |
    | If you prefer to use a backed enum for hierarchy types instead of simple
    | strings, specify the fully-qualified class name here. The enum must be
    | a backed string enum. When set, this takes precedence over the 'types'
    | array above.
    |
    | Example: App\Enums\AncestorType::class
    |
    */

    'type_enum' => env('ANCESTRY_TYPE_ENUM'),

    /*
    |--------------------------------------------------------------------------
    | Events Configuration
    |--------------------------------------------------------------------------
    |
    | Configure event dispatching behavior for hierarchy operations.
    |
    */

    'events' => [
        /*
        |--------------------------------------------------------------------------
        | Events Enabled
        |--------------------------------------------------------------------------
        |
        | When true, Ancestry will dispatch events during hierarchy operations.
        | This enables event-driven workflows such as logging, notifications,
        | or automated responses to hierarchy changes.
        |
        | Events dispatched:
        | - NodeAttached: When a node is attached to a parent
        | - NodeDetached: When a node is detached from its parent
        | - NodeMoved: When a node is moved to a new parent
        | - NodeRemoved: When a node is completely removed from a hierarchy
        |
        */

        'enabled' => env('ANCESTRY_EVENTS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for hierarchy queries. Caching can significantly
    | improve performance for read-heavy workloads where hierarchy structures
    | don't change frequently.
    |
    */

    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Cache Enabled
        |--------------------------------------------------------------------------
        |
        | When true, Ancestry will cache hierarchy query results. This is
        | recommended for production environments where ancestors are read
        | frequently but modified infrequently.
        |
        */

        'enabled' => env('ANCESTRY_CACHE_ENABLED', false),

        /*
        |--------------------------------------------------------------------------
        | Cache Store
        |--------------------------------------------------------------------------
        |
        | Specify which cache store should be used for hierarchy caching. If
        | null, the default cache store will be used. Consider using Redis
        | or Memcached for optimal performance in distributed systems.
        |
        */

        'store' => env('ANCESTRY_CACHE_STORE'),

        /*
        |--------------------------------------------------------------------------
        | Cache Key Prefix
        |--------------------------------------------------------------------------
        |
        | This prefix is prepended to all cache keys used by Ancestry. This
        | helps prevent collisions with other cached data in your application.
        |
        */

        'prefix' => env('ANCESTRY_CACHE_PREFIX', 'ancestry'),

        /*
        |--------------------------------------------------------------------------
        | Cache TTL
        |--------------------------------------------------------------------------
        |
        | The time-to-live (in seconds) for cached hierarchy data. After this
        | duration, cached data will be invalidated and re-fetched from the
        | database. The default is 3600 seconds (1 hour).
        |
        */

        'ttl' => env('ANCESTRY_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, Ancestry will throw exceptions for potentially problematic
    | operations instead of silently handling them. This is recommended for
    | development and staging environments to catch issues early.
    |
    | Strict mode enforces:
    | - Circular reference detection with detailed error messages
    | - Depth limit violations with clear exceptions
    | - Type mismatches in hierarchy operations
    |
    */

    'strict' => env('ANCESTRY_STRICT', true),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | You may specify a different database connection for Ancestry's tables.
    | This is useful if you want to isolate hierarchy data in a separate
    | database. If null, the default database connection will be used.
    |
    */

    'connection' => env('ANCESTRY_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Ancestor Snapshots Configuration
    |--------------------------------------------------------------------------
    |
    | Snapshots capture point-in-time hierarchy chains, preserving historical
    | relationships even when ancestors change. This is useful for maintaining
    | audit trails, commission calculations, or any scenario where you need to
    | know what the hierarchy looked like at a specific moment.
    |
    */

    'snapshots' => [
        /*
        |--------------------------------------------------------------------------
        | Snapshots Enabled
        |--------------------------------------------------------------------------
        |
        | When true, snapshot functionality will be available. You can disable
        | this if you don't need snapshot capabilities in your application.
        |
        */

        'enabled' => env('ANCESTRY_SNAPSHOTS_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Snapshot Model
        |--------------------------------------------------------------------------
        |
        | This model is used to retrieve hierarchy snapshot entries from the
        | database. The model you specify must extend the base AncestorSnapshot
        | class provided by Ancestry.
        |
        */

        'model' => \Cline\Ancestry\Database\AncestorSnapshot::class,

        /*
        |--------------------------------------------------------------------------
        | Snapshots Table Name
        |--------------------------------------------------------------------------
        |
        | The database table used to store hierarchy snapshots.
        |
        */

        'table_name' => env('ANCESTRY_SNAPSHOTS_TABLE', 'ancestor_snapshots'),

        /*
        |--------------------------------------------------------------------------
        | Context Morph Type
        |--------------------------------------------------------------------------
        |
        | This controls the type of polymorphic relationship columns used for
        | the context (the model that the snapshot is attached to).
        |
        | Supported: "morph", "uuidMorph", "ulidMorph"
        |
        */

        'context_morph_type' => env('ANCESTRY_SNAPSHOTS_CONTEXT_MORPH_TYPE', 'string'),

        /*
        |--------------------------------------------------------------------------
        | Ancestor Key Type
        |--------------------------------------------------------------------------
        |
        | The type of key used for the ancestor_id column in snapshots.
        |
        | Supported: "id", "ulid", "uuid"
        |
        */

        'ancestor_key_type' => env('ANCESTRY_SNAPSHOTS_ANCESTOR_KEY_TYPE', 'ulid'),
    ],
];

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
// Here endeth thy configuration, noble developer!                            //
// Beyond: code so wretched, even wyrms learned the scribing arts.            //
// Forsooth, they but penned "// TODO: remedy ere long"                       //
// Three realms have fallen since...                                          //
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
//                                                  .~))>>                    //
//                                                 .~)>>                      //
//                                               .~))))>>>                    //
//                                             .~))>>             ___         //
//                                           .~))>>)))>>      .-~))>>         //
//                                         .~)))))>>       .-~))>>)>          //
//                                       .~)))>>))))>>  .-~)>>)>              //
//                   )                 .~))>>))))>>  .-~)))))>>)>             //
//                ( )@@*)             //)>))))))  .-~))))>>)>                 //
//              ).@(@@               //))>>))) .-~))>>)))))>>)>               //
//            (( @.@).              //))))) .-~)>>)))))>>)>                   //
//          ))  )@@*.@@ )          //)>))) //))))))>>))))>>)>                 //
//       ((  ((@@@.@@             |/))))) //)))))>>)))>>)>                    //
//      )) @@*. )@@ )   (\_(\-\b  |))>)) //)))>>)))))))>>)>                   //
//    (( @@@(.@(@ .    _/`-`  ~|b |>))) //)>>)))))))>>)>                      //
//     )* @@@ )@*     (@)  (@) /\b|))) //))))))>>))))>>                       //
//   (( @. )@( @ .   _/  /    /  \b)) //))>>)))))>>>_._                       //
//    )@@ (@@*)@@.  (6///6)- / ^  \b)//))))))>>)))>>   ~~-.                   //
// ( @jgs@@. @@@.*@_ VvvvvV//  ^  \b/)>>))))>>      _.     `bb                //
//  ((@@ @@@*.(@@ . - | o |' \ (  ^   \b)))>>        .'       b`,             //
//   ((@@).*@@ )@ )   \^^^/  ((   ^  ~)_        \  /           b `,           //
//     (@@. (@@ ).     `-'   (((   ^    `\ \ \ \ \|             b  `.         //
//       (*.@*              / ((((        \| | |  \       .       b `.        //
//                         / / (((((  \    \ /  _.-~\     Y,      b  ;        //
//                        / / / (((((( \    \.-~   _.`" _.-~`,    b  ;        //
//                       /   /   `(((((()    )    (((((~      `,  b  ;        //
//                     _/  _/      `"""/   /'                  ; b   ;        //
//                 _.-~_.-~           /  /'                _.'~bb _.'         //
//               ((((~~              / /'              _.'~bb.--~             //
//                                  ((((          __.-~bb.-~                  //
//                                              .'  b .~~                     //
//                                              :bb ,'                        //
//                                              ~~~~                          //
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ //
