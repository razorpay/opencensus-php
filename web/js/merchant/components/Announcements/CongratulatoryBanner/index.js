import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

const CongratulatoryBanner = ({ user }) => {
  const midList = {
    unicorns: [
      'Fx8KHLQClpbeKN',
      'ECwRvYqexwWYB0',
      'Ba2esp2346gBmV',
      '5wCmb42BKq59c9',
      'EJQ9AMCD7f3iVo',
    ],
    others: [
      'G5KWPzRBj0ysa0',
      'CdgQQVmlzjICNy',
      'A6CclRUMy6wv1i',
      'FyXP0q0xm8gYU7',
      'F0WbfnjD9c9jdm',
      '83MlPdUbSX43fW',
      'EF2ArnDqJofDgg',
      'Eww30TAJfC8QOK',
      'Edt8yrM1vycpzU',
      '41q9EK25oiCplZ',
      'HTAZqhouqx7wet',
    ],
  };

  let bannerText = '';
  if (midList.unicorns.includes(user.current))
    bannerText =
      "You're a Unicorn in a field of horses, and we are proud to partner with you! Best wishes for the road ahead. 🚀🦄";
  else if (midList.others.includes(user.current))
    bannerText = `Best wishes to Team ${user.name} on your latest fund raise. Wishing you continued success from Razorpay. 🚀`;
  else return null;

  return (
    <AnnouncementBanner
      title="Congratulations!"
      canBeClosed
      theme="primary"
      bannerKey={`congratulatory-banner-${user.current}`}
    >
      <span className="display-inline">{bannerText}</span>
    </AnnouncementBanner>
  );
};

export default CongratulatoryBanner;
