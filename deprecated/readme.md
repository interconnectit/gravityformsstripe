## Intro ##
This folder is an entire copy of the Add-On and will be executed when an older version of Gravity Forms (< 2.9) is installed.

## How this works ##
The plugin file `gravityformsstripe/stripe.php` will check if the current version of Gravity Forms is less than 2.9. If it is, the plugin will load the `gf-class-stripe.php` from this folder instead of the main plugin folder.

## Why ##
We are in the process of refactoring this add-on, but the changes we are making require Gravity Forms 2.9. In order to keep the new code clean and also to reduce the risk of regression, we decided to create a full copy of the pre-refactored add-on in this folder. The idea is that we won't fix any issues in this "deprecated" folder. Users will be asked to update Gravity Forms if they run into any problems. 

## Differences ##
There are some minor differences between the files in this folder and the main plugin folder. Here they are:

- The CSS files in the `assets/css/src/` folder have a '-deprecated' suffix in the filename. The build system will copy them to the main plugin 'dist' folder. They will live there side by side with the main plugin CSS files, but with the '-deprecated' suffix.
- The `gf-class-stripe.php` has a few changes
  - The `styles()` method enqueues CSS files from the main plugin folder, so it uses `$base_url = plugins_url( $this->_slug );` to get the base url. 
  - The css files are also suffixed with '-deprecated'.
- The get_base_path() method is overridden to return the path to the deprecated folder.  
- Add the following method at the end of class-gf-stripe.php
```php
/**
 * Ensures that feeds are delayed only in supported contexts.
 *
 * This version of Stripe has an upgrade routine that sets the delayed feed flag for all forms with the new Stripe Element field.
 * This works well, but it also presents a risk that we could be in a situation where feeds are marked as delayed in contexts that do not support delayed feeds.
 * For example, if Gravity Forms gets downgraded to an earlier version after the Stripe add-on is updated.
 * This method returns the $is_delayed flag as false in those cases, ensuring that feeds will be delayed only when feed delay is supported.
 *
 * @param bool   $is_delayed Whether the feed is delayed.
 * @param array  $form       The current form being processed.
 * @param array  $entry      The current entry being processed.
 * @param string $slug       The add-on slug for the feed. i.e gravityformshubspot, gravityformsmailchimp
 *
 * @return bool             True to delay the feed, false to allow immediate processing.
 */
public function maybe_delay_feed_processing( $is_delayed, $form, $entry, $slug )
{
    // If this is not a Stripe feed, abort
    if ( ! $this->is_payment_gateway( $entry['id'] ) ) {
        return $is_delayed;
    }

    // Delayed feed is supported by stripe checkout only.
    $is_delayed_feed_supported = $this->is_stripe_checkout_enabled();

    // If feed delay is not supported in this context, force it to be disabled (otherwiwse it will create problems).
    if ( ! $is_delayed_feed_supported ) {
        return false;
    }

    // Process feed normally.
    return parent::maybe_delay_feed_processing( $is_delayed, $form, $entry, $slug );
}
```

- Modify the update() method so that it looks like this:
```php
/**
 * Run required routines when upgrading from previous versions of Add-On.
 *
 * @since 3.0
 *
 * @param string $previous_version Previous version number.
 */
public function upgrade( $previous_version ) {

    $this->handle_upgrade_3( $previous_version );
    $this->handle_upgrade_3_2_3( $previous_version );
    $this->handle_upgrade_3_3_3( $previous_version );

    // Handle upgrade to Stripe 6.0 while on an older (2.8) version of Gravity Forms core.
    require_once plugin_dir_path( __FILE__ ) . '../includes/upgrade/class-gf-stripe-upgrade-handler.php';
    $upgrade_handler = new GF_Stripe_Upgrade_Handler( $this );
    $upgrade_handler->handle_upgrade_6( $previous_version );
}
```
