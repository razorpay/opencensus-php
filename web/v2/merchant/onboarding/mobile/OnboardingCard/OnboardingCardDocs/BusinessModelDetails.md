## BusinessModelDetails

It renders Business Type , Business Category select inputs (and Business Model textArea).

it uses

1.  activation_data.onboarding_milestone to render all the fields (i.e nothing is rendered when onboarding_milestone is set to something that is not null)

2.  activation_data.onboarding_card_details.business_type (same as hasBusinessModel state) to determine whether Business Model Field to be shown or not.
