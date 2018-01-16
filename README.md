# Gamajo Dashboard Glancer

A class to copy into your WordPress plugin, to make adding custom post type counts to the _At a Glance_ dashboard widget considerably easier.

[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/gamajo/dashboard-glancer/badges/quality-score.png?s=543e04781b27f58b1e37ba742f760c0c5ba82297)](https://scrutinizer-ci.com/g/gamajo/dashboard-glancer/)

## Description

WordPress 3.8 introduced the _At a Glance_ dashboard widget, as a replacement for the _Right Now_ widget. The new widget contains an action hook, `dashboard_glance_items`, which allows developers to insert extra list items into the widget. Although grabbing the number of entries of a certain post type, and maybe a specific status within that post type, and displaying within markup is not tricky, it can't be done as minimally as what this class allows. Add in the desire for wanting to include counts for multiple post types, or multiple statuses, and this class comes into its own. See the Usage section for how simple the code is.

## Installation

This isn't a WordPress plugin on its own, so the usual instructions don't apply. Instead:

### Manually install class

1. Copy [`class-gamajo-dashboard-glancer.php`](class-gamajo-dashboard-glancer.php) into your plugin. It can be into a file in the plugin root, or better, an `includes` directory.
2. Ensure the class file is available to whichever code will be using it.

  ~~~php
  // Require the new class (change to your correct path)
  if ( ! class_exists( 'Gamajo_Dashboard_Glancer' ) ) {
      require plugin_dir_path( 'includes/class-gamajo-dashboard-glancer.php' );
  }
  ~~~

or:

### Install class via Composer
1. Tell Composer to install this class as a dependency: `composer require gamajo/dashboard-glancer`
2. Recommended: Install the Mozart package: `composer require coenjacobs/mozart --dev` and [configure it](https://github.com/coenjacobs/mozart#configuration).
3. The class is now renamed to use your own prefix, to prevent collisions with other plugins bundling this class.

## Usage

Within your plugin files, either as a function, or as part of a class, implement something like:

~~~php
// Hook into the widget (or any hook before it!) to register your items.
add_action( 'dashboard_glance_items', 'prefix_add_dashboard_counts' );
function prefix_add_dashboard_counts() {
    $glancer = new Gamajo_Dashboard_Glancer;
    $glancer->add( 'my_cpt' ); // show only published "my-cpt" entries
}
~~~

That's it!

### More Usage Examples

~~~php
// Lots of examples of how to add given here, you only need one for each combination of
// multiple post types and multiple statuses.
add_action( 'dashboard_glance_items', 'prefix_add_dashboard_counts' );
function prefix_add_dashboard_counts() {
    $glancer = new Gamajo_Dashboard_Glancer;
    $glancer->add( 'my_cpt' ); // show only published "my-cpt" entries
    $glancer->add( 'my_cpt', 'draft' ); // show only draft "my-cpt" entries
    $glancer->add( 'my_cpt', array( 'publish', 'draft' ) ); // show only published and draft "my-cpt" entries

    // Show only published and draft entries for all three post types
    $my_post_types = array( 'ingredients', 'recipes', 'meal_plan' );
    $glancer->add( $my_post_types, array( 'publish', 'draft' ) );

    // Supports custom statuses as well
    $my_statuses = array( 'pitch', 'assigned', 'in-progress', 'needs-edit', 'ready-to-publish' );
    $glancer->add( 'post', $my_statuses );
}
~~~

Remember, that code above shows lots of examples of how you can add multiple post types and multiple statuses in one go. For the simplest case, you would instantiate the class, and do a single call to the `add()` method and that's it.

## Notes

The textual label is taken from the custom post type registration itself. It will use the singular label (e.g. _Story_) if the count is exactly 1, or the plural name (e.g. _Stories_) otherwise. If the status is not `publish`, then it will append the status to disambiguate. If the item count is zero, then nothing will show up, to keep visual clutter to a minimum.

The class will automatically output any items still registered, at priority 20 of the `dashboard_glance_items` action hook. If you want to output at an earlier priority, then just call the `show()` method on the object that has the items registered, when hooked in to that earlier priority (here, 8):

~~~php
add_action( 'dashboard_glance_items', 'prefix_add_dashboard_counts', 8 );
function prefix_add_dashboard_counts() {
    $glancer = new Gamajo_Dashboard_Glancer;
    $glancer->add( 'my_cpt' ); // show only published "my-cpt" entries
    $glancer->show();
}
~~~

Once `show()` is called, all registered items become unregistered, so that `show()` being called again (at priority 20, or manually) will not result in duplicate items showing on the widget.

The class is not a singleton, so if you add items in one function, but want to show early in another, you'll need to pass the object across, likely via a global variable. As a non-singleton, you could also register some items to show early, and some to show late, by instantiating two objects from the `Gamajo_Dashboard_Glancer` class, but only explicitly calling `show()` on one of them.

## Backwards Compatibility for WP 3.7 Right Now Widget

This project also includes the [`Gamajo_Dashboard_RightNow`](class-gamajo-dashboard-widget-rightnow.php) class which is an extension of the main `Gamajo_Dashboard_Glancer` class. This can be instantiated and added to within a function hooked to the `right_now_content_table_end` hook, to provide the same information in the WordPress 3.7 Right Now widget.

~~~php
// Hook into the widget (or any hook before it!) to register your items.
add_action( 'right_now_content_table_end', 'prefix_add_dashboard_counts_old' );
function prefix_add_dashboard_counts_old() {
    $glancer = new Gamajo_Dashboard_RightNow;
    $glancer->add( 'my_cpt' ); // show only published "my-cpt" entries
}
~~~

## Contributions

Contributions are welcome - fork, fix and send pull requests against the `develop` branch please.

## License

GPL-2.0+, so feel free to use in any WordPress project. Let me know when you do (not a requirement of usage) as I'd love to know where my code is being used!

## Credits

Built by [Gary Jones](https://twitter.com/GaryJ)  
Copyright 2013 Gary Jones, [Gamajo Tech](http://gamajo.com/)
