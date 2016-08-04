# mp_forms

This is a Contao 3.5+ extension that finally allows you to create real forms over multiple pages.

After installing the module, you have a new setting in the form definitions where you can define the GET parameter
 you want the extension to work with. By default, this is `step` so it will generate `step=1`, `step=2` etc. in the URL.
If, for whatever reason `step` is already in use on your webpage, you can change the settings there.

Moreover, you'll get a new form field called `Page break`. Every time you use this form field, the module will insert a
page break in the form.

`mp_forms` validates if a user manually wants to go to step 3 and did not fill in step 1 or 2. I this case the user will be redirected
to step 1 (obviously, only if you had required fields on step 1).

Note that you should not be using a regular submit form field to finish your form but use the `Page break` again. Otherwise, you
won't have any `back` button displayed. `mp_forms` will automatically detect the last `Page break` as behaving like the form submit.

    
## InsertTags

You can use exactly the same parameters as you have in your template also via InsertTags. However you have to pass the form ID:

* `{{mp_forms::<form id>::current}}`
* `{{mp_forms::<form id>::total}}`
* `{{mp_forms::<form id>::percentage}}`
* `{{mp_forms::<form id>::numbers}}`

Note that they can be especially useful together with a `Custom HTML` front end module.
Let's assume you want to display a progress bar for form ID `5`:

````html
<div class="progress">
    <div class="progress-bar">
        <div class="progress-bar-inner" style="width:{{mp_forms::5::percentage}}%"></div>
    </div>
    <div class="numbers">{{mp_forms::5::numbers}}</div>
</div>
```
