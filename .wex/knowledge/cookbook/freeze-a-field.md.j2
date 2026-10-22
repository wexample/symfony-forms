## Show a value someone must not change

Two needs that look like one, and the difference decides which of the two you reach for.

- A **frozen field** is a field: bound to the model, rendered with the look of its type,
  refused on submission. A creation date on an edit screen, a weight the device wrote, a
  column one role may edit and another may only read.
- A **display value** is not a field at all: nothing bound, nothing submitted. It sits in the
  field order so a record reads as one block instead of splitting into a form and a summary
  beside it.

### A frozen field

Nothing new to learn: it is Symfony's own `disabled` option, decided in `buildForm`.

```php
$builder->add(
    'dateCreated',
    DateInputType::class,
    [
        self::FIELD_OPTION_NAME_LABEL => true,
        'disabled' => true,
    ]
);
```

What `disabled` buys is the only guarantee that matters: **Symfony ignores whatever is
submitted for the field and keeps the model's value**. A `readonly` attribute alone is
decoration — a forged post carries whatever it likes — and a browser submits a `readonly`
input like any other, so the refusal has to happen on the server. It does, and a test in this
package posts a new value for every field of a frozen form and reads the object back
unchanged.

What the package adds is the rendering. The theme renders a frozen field `readonly`, never
`disabled`, and drops its `required`:

- `disabled` in HTML greys the control, takes it out of the tab order and is passed over by
  most screen readers. It says *unavailable*, where the truth is *settled* — and a form read
  against IEC 62366 cannot afford the confusion.
- `readonly` leaves the field focusable, readable and selectable.
- a `required` nobody can satisfy would make the browser refuse every submission of the form,
  so a frozen field never carries it.

### The conditional

Per field, in the form, never in the template — a template that decides it protects nothing:

```php
public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $frozen = ! $options['can_edit_identity'];

    $builder->add('name', TextInputType::class, ['disabled' => $frozen]);
}

public function configureOptions(OptionsResolver $resolver): void
{
    parent::configureOptions($resolver);
    $resolver->setDefaults(['can_edit_identity' => false]);
}
```

The option is the application's to name and to compute — from a role, from a state, from the
record itself through a `PRE_SET_DATA` listener. The same form class then serves the two
screens, and the decision is on the server in both.

### The types with no `readonly` in HTML

`readonly` exists for a text input and not for a `select`, a radio group, a checkbox or a
file field. For those, the theme renders the **value they hold** in a readonly field that
carries no `name` at all, so nothing of them reaches the server:

| Type | What a frozen one says |
|---|---|
| `SelectInputType`, `RadioInputType` | the label of what is selected, translated as the editable rendering translates it; comma-separated when several |
| `SwitchInputType` | `field.<name>.choice.1.label` or `.0.label` in the form's domain |
| `FileInputType` | what the model holds — the stored name, the path |
| `OtpInputType`, `EmojiPickerType` | the value itself |

A switch has no `choices` to read a label from, so it reads the two keys a radio group would
have had. Write them in the form's yaml, because a switch is almost never "yes" and "no":

```yaml
field:
  active:
    choice:
      1:
        label: Active
      0:
        label: Deactivated
```

Leave them out and the key shows on the screen, which is the package telling you what to
write.

### A display value

```php
$builder->add(
    'dateCreated',
    DisplayValueType::class,
    [
        self::FIELD_OPTION_NAME_LABEL => true,
        'data' => $record->getDateCreated()->format('j F Y'),
    ]
);
```

It renders a label and a value, in the shape of a field group so it lines up with the real
ones, and an empty one shows a dash rather than a blank space — a blank reads as something
missing from the page, a dash as something missing from the thing described. No element a
browser submits is written, and `mapped` is false, so there is nothing to forge: this is the
one of the two that cannot be posted to at all.

**The value arrives formatted.** A date stored in UTC, shown in the reader's timezone, in
their language, is a question about the reader, and the field does not know them — the
application formats and passes the string.
