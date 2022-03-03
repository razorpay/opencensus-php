<?php

namespace RZP\Notifications\AdminDashboard;

 class Events
 {
     const NEEDS_CLARIFICATION = 'NEEDS_CLARIFICATION';

     const WHATSAPP_TEMPLATE_NAMES = [
         self::NEEDS_CLARIFICATION => 'ncwhatsapp_multiplerequirement',
     ];

     const NO_OF_DOCUMENTS_NEEDS_CLARIFICATION_TEMPLATE_NAMES = [
         1 => 'ncwhatsapp_one_requirement',
         2 => 'ncwhatsapp_two_requirement',
         3 => 'ncwhatsapp_three_requirement1',
         4 => 'ncwhatsapp_four_requirement1',
         5 => 'ncwhatsapp_five_requirement',
         6 => 'ncwhatsapp_six_requirement2'
     ];

     const NO_OF_DOCUMENTS_NEEDS_CLARIFICATION_TEMPLATES = [
         1 => 'Hi {1},
Thanks for choosing Razorpay. There is a requirement that needs to be completed before we can activate your account.
- {2}

Please share the links/proofs by replying to this ticket.
Regards,
Team Razorpay',
         2 => 'Hi {1},
Thanks for choosing Razorpay. There are a few requirements that need to be completed before we can activate your account.
- {2}
- {3}

Please share the links/proofs by replying to this ticket.
Regards,
Team Razorpay',
         3 => 'Hi {1},
Thanks for choosing Razorpay. There are a few requirements that need to be completed before we can activate your account.
- {2}
- {3}
- {4}

Please share the links/proofs by replying to this ticket.
Regards,
Team Razorpay',
         4 => 'Hi {1},
Thanks for choosing Razorpay. There are a few requirements that need to be completed before we can activate your account.
- {2}
- {3}
- {4}
- {5}

Please share the links/proofs by replying to this ticket.
Regards,
Team Razorpay',
         5 => 'Hi {1},
Thanks for choosing Razorpay. There are a few requirements that need to be completed before we can activate your account.
- {2}
- {3}
- {4}
- {5}
- {6}

Please share the links/proofs by replying to this ticket.
Regards,
Team Razorpay',
         6 => 'Hi {1},
Thanks for choosing Razorpay. There are a few requirements that need to be completed before we can activate your account.
- {2}
- {3}
- {4}
- {5}
- {6}
- {7}

Please share the links/proofs by replying to this ticket.
Regards,
Team Razorpay'
     ];
 }
