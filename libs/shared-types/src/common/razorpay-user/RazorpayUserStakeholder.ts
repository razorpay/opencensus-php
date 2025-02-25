
export type RazorpayUserStakeholder = {
    id: string;
    merchant_id: string;
    name: null | string;
    email: null | string;
    phone_primary: null | string | number;
    phone_secondary: null | string | number;
    director: unknown;
    executive: unknown;
    percentage_ownership: unknown;
    notes: unknown[];
    poi_identification_number: unknown;
    poi_status: unknown;
    pan_doc_status: unknown;
    poa_status: unknown;
    aadhaar_esign_status: unknown;
    aadhaar_verification_with_pan_status: unknown;
    aadhaar_pin: unknown;
    aadhaar_linked: number;
    bvs_probe_id: unknown;
    audit_id: unknown;
    created_at: number;
    updated_at: number;
    verification_metadata: string;
    deleted_at: null | number;
  };