type SFLeadPayload = Record<string, string | number> & {
  Name: string;
  Email: string;
};

type FullNameType = {
  FirstName: string;
  LastName: string;
};

const ALREADY_EXIST_MESSAGES = [
  'The email has already been taken',
  'The email Id entered by you already exists in our records',
  'already exist',
];

const EMAIL_ALREADY_TAKEN_ERR_MSG = 'This email has already been taken';
export const GENERIC_ERR_MSG = 'Something went wrong. Please try again after sometime.';

const getNameSplit = (fullName: string): FullNameType => {
  const nameArray = fullName.trim().split(' ');
  let LastName = nameArray.slice(-1).join(' ');
  const FirstName = nameArray.slice(0, -1).join(' ') || LastName;
  LastName = LastName === FirstName ? 'NA' : LastName;
  return { FirstName, LastName };
};

export function submitSFLead({ Name, ...payload }: SFLeadPayload): Promise<object> {
  const url = `/user/salesforce_event`;
  return fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      ...getNameSplit(Name),
      ...payload,
    }),
  })
    .then((response) => response.json())
    .then((resData) => {
      const SFErrors = resData?.errors || [];
      if (SFErrors.length) {
        if (ALREADY_EXIST_MESSAGES.some((errorMessage) => SFErrors.includes(errorMessage))) {
          throw new Error(EMAIL_ALREADY_TAKEN_ERR_MSG);
        } else {
          throw new Error();
        }
      }
      return resData;
    })
    .catch((error) => {
      if (error.message === EMAIL_ALREADY_TAKEN_ERR_MSG) {
        throw new Error(error.message);
      } else {
        throw new Error(GENERIC_ERR_MSG);
      }
    });
}
