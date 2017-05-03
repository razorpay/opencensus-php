<?php

namespace RZP\Gateway\Aeps\Icici;

use RZP\Models\Bank\IFSC;

class BankIin
{
    protected static $map = [
        IFSC::CNRB => '607396',
        IFSC::ICIC => '508534',
    ];

    //TODO update mapping for below banks also
    /*
        Allahabad UP Gramin Bank    607091
        Andhra Bank    607076
        Andhra Pradesh Gramin Vikash Bank    607198
        Andhra Pragathi Grameena Bank    607121
        Assam Gramin Vikash Bank    607064
        Axis Bank    607153
        Bangiya Gramin Vikash Bank    607063
        Bank Of Baroda    606985
        Bank of India    508505
        Bank of Maharashtra    607387
        Baroda Gujarat Gramin Bank     606995/607046/608144
        Baroda Rajasthan Kshetriya Gramin Bank    607280
        Baroda Uttar Pradesh Gramin Bank     606993
        Bhartiya Mahila Bank    608114
        Bihar Gramin Bank    607377
        Canara Bank    607396
        Central Bank of India    607264
        Central Madya Pradesh Gramin Bank    607071
        Chaitanya Godavari Gramin Bank    607080
        Chhattisgharh Rajya Gramin Bank    607214
        Corporation Bank    607184
        Dena Bank    508547
        Dena Gujarat Gramin Bank    607099
        Ellaquai Dehati Bank    607218
        Gramin bank of Aryavart    607024
        HDFC Bank    607152
        Himachal Pradesh Gramin Bank    607140
        ICICI Bank    508534
        IDBI Bank    607095
        IDFC Bank    608117/608118/608116
        Indian bank    607105
        Indian Overseas Bank    607126
        IndusInd Bank    607189
        Jammu & Kashmir Bank     607440
        Jharkhand Gramin Bank    607021
        Karnataka Vikas Grameena Bank     607122
        Karur Vysya Bank    508662
        Kerala Gramin Bank    607399
        Langpi Dehangi Rural Bank    607202
        Madhya Bihar Gramin Bank    607136
        Madhyanchal Gramin Bank    607232
        Malwa Gramin Bank    607241
        Manipur Rural Bank     607062
        Meghalaya Rural Bank    607206
        Narmada Jhabua Gramin Bank    607022
        Odisha Gramya Bank     607060
        Oriental Bank of Commerce    607085/607041
        Pallavan Grama Bank    607052
        Pandyan GB    607059
        Pragathi Krishna Gramin Bank    607400
        Prathama Bank    607124
        Punjab & Sind Bank    607087
        Punjab Gramin Bank    607138
        Punjab National Bank    607027
        Purvanchal Gramin Bank    607212
        Rajasthan Marudhara Gramin Bank    607509
        Ratnakar Bank    607393
        Saptagiri Grameena Bank    607053
        Sarva Haryana Gramin Bank    607139
        Sarva UP Gramin Bank     607135
        Saurashtra Gramin Bank    607200
        South Indian Bank    607475
        State  bank of Bikaner and Jaipur    606987
        State bank of Hyderabad    607901
        State Bank of India    607094
        State Bank of Mysore    606989
        State Bank of Patiala    606990
        State Bank of Travancore    606991
        Sutlej Gramin Bank    607310
        Syndicate Bank    607580
        Telangana Grameena Bank    607195
        Tripura Gramin Bank    607065
        UCO Bank    607066
        Union Bank of India    607161/508500
        United Bank Of India    607646
        Utkal Gramin Bank    607234
        Uttar Banga Kshetriya Gramin Bank     607073
        Uttar Bihar Grameen Bank     607069
        Uttarakhand Gramin Bank    607197
        Vananchal Gramin Bank    607210
        Vidarbha Konkan Gramin Bank    607020
        Vijaya Bank    607075
     */
}
