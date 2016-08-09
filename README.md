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

## Frontend module to display a step navigation

This module ships with a front end module that allows you to easily build a front end navigation for
each of your steps.
Unstyled it might look something like this in the end:

![Example for step navigation](docs/navigation_example.png)

Note that by default steps will just be named `Step x` in every language. The `Page break` form field
label field will be used for the navigation if you provide it.
    
## Insert tags

There are insert tags you can use to fetch information about the state of the form:

| Insert tag  |  Description | Example  |
|---|---|---|
| `{{mp_forms::<form id>::current}}`  |  Contains the current step you are on | 2  |
| `{{mp_forms::<form id>::total}}`  |  Contains the total steps of your form | 5  |
| `{{mp_forms::<form id>::percentage}}`  |  Contains the percentage of your progress | 20  |
| `{{mp_forms::<form id>::numbers}}` | Contains a classic `x of y` display | `2 / 5`|

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
