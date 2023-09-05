import React, { useEffect, useState } from 'react';
import { merchantFetch } from 'merchant/utils/ajax';

function UpdatedBy({ dispute }) {
  const userId = dispute?.lifecycle[0]?.user_id;
  const [user, setUser] = useState();

  useEffect(() => {
    if (userId)
      merchantFetch(`users/fetch_for_merchant/${userId}`).then((res) => {
        if (res?.data?.id) {
          setUser(res.data);
        }
      });
  }, []);

  return user?.name ? (
    <span>
      {user.name} ({user.email})
    </span>
  ) : (
    '--'
  );
}

export default UpdatedBy;
