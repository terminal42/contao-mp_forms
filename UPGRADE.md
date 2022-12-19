# Upgrading from 4.x to 5.x

## BC Breaks

### Developers

The code base received a complete rewrite from scratch. If you worked with the `MPFormsFormManager` before, go for 
proper DI and the `FormManagerFactoryInterface` to access the `FormManager` for a given form ID:

```
$manager = $this->formManagerFactory->forFormId($formId);
```

### Users

- The `MPForms - Steps` front end module does not provide `even` and `odd` CSS classes anymore.
- The `MPForms - Steps` front end module does not provide the `forbidden` CSS class anymore. Instead, you have more clear
  `accessible` and `inaccessible` classes plus the current one gets `current` now.
- The template `form_mp_form_page_switch` has been renamed to `form_mp_form_pageswitch` in order to support custom
  template selection in the back end. You may need to adjust your custom templates as well.

## New

- The insert tag `{{mp_forms::<form id>::step::label}}` is new and outputs the label of the current step