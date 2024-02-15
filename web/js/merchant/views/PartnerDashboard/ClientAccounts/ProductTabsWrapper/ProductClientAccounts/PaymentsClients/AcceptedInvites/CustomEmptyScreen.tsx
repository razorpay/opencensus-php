import React from 'react';

type CustomEmptyScreenProps = {
  isFilterSearchUsed: boolean;
};
const CustomEmptyScreen = ({ isFilterSearchUsed }: CustomEmptyScreenProps): JSX.Element => {
  return isFilterSearchUsed ? (
    <div className="empty-table-message">
      <h4>No Search results found</h4>
    </div>
  ) : (
    <div className="empty-table-message">
      <h4>All Accepted Invites</h4>
      <p className="m-t">
        All accepted invites will be visible here once the client has accepted the invite sent by
        you.
      </p>
    </div>
  );
};
export default CustomEmptyScreen;
