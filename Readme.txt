=== Post Notification ===
Contributors: morty
Donate link: http://pn.xn--strbe-mva.de/forum.php?req=thread&id=4
Website: http://pn.xn--strbe-mva.de
Tags: notification, email, mail, post, subscribe, subscribe2, german, deutsch, french
Requires at least: 2.2
Tested up to: 2.7
Stable tag: 1.2.41


With each new post an email is sent to every registered User in the Database. The email can be text or HTML.

== Description ==
Post Notification 1.2 is **not compatible with WP < 2.2**. Please use Post Notification 1.1.x. You can find it, if you follow the "other versions" link
in the FYI-box on the right.  

* **[Changelog](http://dev.wp-plugins.org/browser/post-notification/trunk/post_notification/changelog.txt?format=txt)**
* **[Release Notes](http://pn.xn--strbe-mva.de/forum.php?req=main&id=1)**

Features: (**new in 1.2**) 

* **Support for WP 2.3**
* Can handle thousands of subscribers. **And has a simple back end to manage them.**
* **Subscribers can choose categories.**
* Easy to translate:The frontend should take a few minutes, the backend a bit longer. Please send me your translations! 
* The Post can be included, if you wish, only up to the more-tag.
* Mails can be sent as HTML or text.
* If you send text mails, the post is formated.
* Double Opt-in
* Frontend and mails are configured via templates. It is easy to change everything without messing in the code.
* Easy im- and export of Emails
* Every recipient gets his personal mail, with a link to change his subscribed categories or to unsubscribe.
* Nervous Finger option (Mail isn't sent right away - so you still have time to change things once you posted.)
* You can decide how many mails are sent in a burst and how long to pause between bursts.
* Decide on a per post basis whether to send a mail, or not, or just use the default.
* Captchas
* **Integrates into almost any theme without tweaks.**
* Translations: Deutsch (German), French (More welcome! Front end translations or modifications, too!)
* Does not need WP-Cron or anything like that.
* **Possebility to adjust mails with userfunctions**
* much more

= Changes in the templates since 1.1 =

* **select.tmpl** has been added.
* **activated.tmpl** has been removed.
* **strings.php** has five new strings.
* **unsubscribe.tmpl** now needs a @@conf_url.
* Mail templates can now use @@author. And @@unsub changed to @@conf_url.
* **subscribe.tmpl** need @@vars in the form.



= Where is the difference to Subscribe2? =

Subscribe2 as well as Post Notification are based on the same plugin. While Subscribe2 has it's emphasis on a
nice user interface and is more easy to configure this plugin is also suitable for professional sites who want
to send several thousand mails as day. There are two main reasons for this:

1. PN sends a "personal" email to each subscriber with a link to change his settings.
2. A subscriber can choose categories without having to register a Wordpress user.


== Installation ==
IMPORTANT: If you have an earlier version installed do not deactivate it. Just overwrite it.

1. Open the zip.
2. go into the post-notification directory.
3. Copy the post_notification (Underline!!) dir into you plugin dir.
4. You can have a look at the pictures if you want. Unfortunately they are packed into the file automatically

Activate the plugin, configure it carefully (options->Post Notification) and off you go.
For support please see the Forum: http://pn.xn--strbe-mva.de/
An please report problems. I can only fix stuff if I know that they are broken.

= If you are updating =
You might get errors, because old translations are not working. You can delete those dirs. (ru_RU, etc)

You have to update your old templates:

* **select.tmpl** has been added.
* **activated.tmpl** has been removed.
* **strings.php** has five new strings.
* **unsubscribe.tmpl** now needs a @@conf_url.
* Mail templates can now use @@author. And @@unsub changed to @@conf_url.
* **subscribe.tmpl** need @@vars in the form.

== Frequently Asked Questions ==
See the Forum : http://pn.xn--strbe-mva.de/


== Screenshots ==

1. Lots of configuration options
2. Decide whether to send a notification on a per post basis.
3. Filter the list of subscribers.
4. Manage the emails. You can add, remove and change the settings of a list of email addresses.

