import React from 'react';
import { StyledProfilePhoto } from './styled';

interface ProfilePhotoPropsInterface {
  imageUrl: string;
}

const ProfilePhoto = ({ imageUrl }: ProfilePhotoPropsInterface): JSX.Element => {
  return (
    <StyledProfilePhoto>
      {imageUrl ? <img src={imageUrl} /> : <i className="i i-profile" />}
    </StyledProfilePhoto>
  );
};

export default ProfilePhoto;
