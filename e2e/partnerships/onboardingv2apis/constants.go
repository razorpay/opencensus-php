package e2e

const (
	TagOnboardingApi                         = "onboardingapi"
	ActivatedKycPending                      = "activated_kyc_pending"
	UnderReview                              = "under_review"
	NeedsClarification                       = "needs_clarification"
	GetBvsValidationIdSelectQueryForArtefact = "SELECT validation_id FROM bvs_validation " +
		"WHERE  owner_id = '%s' AND owner_type = 'merchant' AND artefact_type = '%s' order by created_at desc limit 1 "
)
