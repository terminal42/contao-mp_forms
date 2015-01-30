# mp_forms

This is a Contao 3.4+ extension that finally allows you to create real forms over multiple pages.

After installing the module, you have a new setting in the form definitions where you can define the GET parameter
 you want the extension to work with. By default, this is `step` so it will generate `step=1`, `step=2` etc. in the URL.
If, for whatever reason `step` is already in use on your webpage, you can change the settings there.

Moreover, you'll get a new form field called `Page break`. Every time you use this form field, the module will insert a
page break in the form.

`mp_forms` validates if a user manually wants to go to step 3 and did not fill in step 1 or 2. I this case the user will be redirected
to step 1 (obviously, only if you had required fields on step 1).

When you use a custom form field template for the page break form field you can use the following variables in your template:

| Parameter name  |  Description | Example  |
|---|---|---|
| $this->current  |  Contains the current step you are on | 2  |
| $this->total  |  Contains the total steps of your form | 5  |
| $this->percentage  |  Contains the percentage of your progress | 20  |
| $this->numbers | Contains a classic `x of y` display | `2 / 5`|

    
## InsertTags

You can use exactly the same parameters as you have in your template also via InsertTags. However you have to pass the form ID:

* `{{mp_forms::<form id>::current}}`
* `{{mp_forms::<form id>::total}}`
* `{{mp_forms::<form id>::percentage}}`
* `{{mp_forms::<form id>::numbers}}`