mp_forms
========

This is a Contao 2.11 extension that finally allows you to create real forms over multiple pages.

After installing the module, you have a new setting in the form definitions where you can define the GET parameter
you want the extension to react on. By default, this is "step" so it will generate "step=1", "step=2" etc. in the URL.
For whatever reason you might have "step" already on your webpage so you can change the settings there.
The cool thing is that mp_forms will prevent all other GET parameters so you can control the module to not interfere with
other modules on your webpage.

Moreover, you'll get a new form field called "Page break". Every time you use this form field, the module will insert a
page break in the form.

Another cool feature is that if a user manually wants to go to step 3 and did not fill in step 1 or 2, he/she'll be redirected
to step 1.

InsertTags
---

Get current form step:   {{mp_forms::\<form id\>::current}}

Get the number of steps: {{mp_forms::\<form id\>::total}}