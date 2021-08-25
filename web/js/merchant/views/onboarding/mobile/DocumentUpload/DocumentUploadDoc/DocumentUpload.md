## DocumentUpload

it renders all the Ducument Upload components needed for each type of merchant. On upload activation_data.documents is updated as per the kind of document uploaded.

it uses

1. activation_data.business_overview.business_type to determine whether to show Business Registration Proof or not (only shown when business type is PROPRIETORSHIP and submitted without uploading business proof url)

2. activation_data.business_overview.business_type to determine whether to show Certificate of Incorporation or not (only shown when business type is
   a. PROPRIETORSHIP and has uploaded business proof type doc
   b. any other registered business and has uploaded business proof url
   )
