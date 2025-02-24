import React from 'react';
import { Alert, Box } from '@razorpay/blade/components';
import { Field } from 'redux-form';
import InputField from 'common/ui/Forms/InputField';
import { required, email, name, phoneIndia } from 'common/utils/validators';
import AsyncButton from 'react-async-button';

interface City {
  name: string;
  zipcodes: string[];
  timezone: string;
  'region_name/district_name': string;
}

interface State {
  name: string;
  cities: Record<string, City>;
}
interface PosAgentFormProps {
  roles: Record<string, { label: string; desc: string }>;
  visibleFields: Record<string, boolean>;
  ctaText: string;
  handleSubmit: () => void;
  shouldShowNonPOSAlert: boolean;
  selectedRole: string;
  cities: Record<string, City>;
  stateList: State;
  currentUserDetails: {
    email: string;
    contact_mobile: string;
  };
  teamNames: string[];
}

const PosAgentForm: React.FC<PosAgentFormProps> = ({
  roles,
  visibleFields,
  ctaText,
  shouldShowNonPOSAlert,
  handleSubmit,
  selectedRole,
  currentUserDetails,
  teamNames,
  cities,
  stateList,
}: PosAgentFormProps) => {
  return (
    <form>
      <div>
        <Box display="grid" gridTemplateColumns={'1fr 1fr'} gap="spacing.5">
          <Box>
            <label>Role</label>
            <div className="input-container">
              <Field name="role" component="select" class="form-control">
                {Object.keys(roles).map((role) => (
                  <option key={role} value={role}>
                    {roles[role].label}
                  </option>
                ))}
              </Field>
            </div>
          </Box>

          <Box flex={1}>
            <label>Agent Name</label>
            <div className="input-container">
              <Field
                name="metadata.name"
                component={InputField}
                class="form-control"
                placeholder="Rahul Singh"
                autoFocus={true}
                validate={[required(), name('Invalid agent name')]}
              />
            </div>
          </Box>

          {visibleFields.email && (
            <Box flex={1}>
              <label>Email ID</label>
              <div className="input-container">
                <Field
                  name="posEmail"
                  component={InputField}
                  class="form-control"
                  placeholder="johndoe@razorpay.com"
                  validate={[
                    required(),
                    email('Invalid Email'),
                    (value) => {
                      if (value === currentUserDetails.email) {
                        return "You can't invite yourself";
                      }

                      return null;
                    },
                  ]}
                />
              </div>
            </Box>
          )}

          <Box flex={1}>
            <label>Phone number</label>
            <div className="input-container required">
              <Field
                required={true}
                name="metadata.mobile"
                component={InputField}
                class="form-control"
                placeholder="9876543210"
                validate={[
                  required(),
                  phoneIndia('Please enter 10 digit number'),
                  (value) => {
                    if (value === currentUserDetails.contact_mobile) {
                      return "You can't invite yourself";
                    }

                    return null;
                  },
                ]}
              />
            </div>
          </Box>

          <Box flex={1}>
            <label>Team</label>
            <div className="input-container">
              <Field name="metadata.team" component="select" class="form-control">
                {teamNames.map((team) => (
                  <option key={team} value={team}>
                    {team}
                  </option>
                ))}
              </Field>
            </div>
          </Box>

          <Box flex={1}>
            <label>Hiring Manager</label>
            <div className="input-container">
              <Field
                name="metadata.hiring_manager"
                component={InputField}
                class="form-control"
                placeholder="John Doe"
                validate={[required(), name('Invalid Name')]}
              />
            </div>
          </Box>

          <Box flex={1}>
            <label>Business Unit Head</label>
            <div className="input-container">
              <Field
                name="metadata.bu_head"
                component={InputField}
                class="form-control"
                placeholder="John Will"
                validate={[required(), name('Invalid Name')]}
              />
            </div>
          </Box>

          <Box flex={1}>
            <label>Zone</label>
            <div className="input-container">
              <Field
                name="metadata.zone"
                component={InputField}
                class="form-control"
                placeholder="Koramangala"
                validate={[required(), name('Invalid Zone')]}
              />
            </div>
          </Box>

          <Box flex={1}>
            <label>State</label>
            <div className="input-container">
              <Field name="metadata.state" component="select" class="form-control">
                {Object.keys(stateList).map((stateCode) => (
                  <option key={stateCode} value={stateList[stateCode]?.name}>
                    {stateList[stateCode]?.name}
                  </option>
                ))}
              </Field>
            </div>
          </Box>

          <Box flex={1}>
            <label>City</label>
            <div className="input-container">
              <Field name="metadata.city" component="select" class="form-control">
                {Object.keys(cities).map((cityName) => (
                  <option key={cityName} value={cityName}>
                    {cityName}
                  </option>
                ))}
              </Field>
            </div>
          </Box>
        </Box>
        {visibleFields.role && (
          <Box marginTop={'spacing.6'}>
            {!shouldShowNonPOSAlert && roles[selectedRole]?.desc ? (
              <div className="alert alert-info">{'Can only access POS Sales Dashboard'}</div>
            ) : null}
            {shouldShowNonPOSAlert ? (
              <Alert
                marginBottom={'spacing.6'}
                isDismissible={false}
                color="negative"
                description={"You're adding a role which is associated with your merchant profile"}
              />
            ) : null}
          </Box>
        )}
        <div className="form-group">
          <AsyncButton
            className="btn btn-primary btn-block"
            text={ctaText}
            type="submit"
            pendingText="Processing..."
            onClick={handleSubmit()}
          />
        </div>
      </div>
    </form>
  );
};

export default PosAgentForm;
