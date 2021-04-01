import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default React.memo(({ children, docURL }) => {
  return (
    <AnnouncementBanner title="Important Announcement!" theme="warning">
      <span class="display-inline">
        {children}{' '}
        <a class="btn-link" href={docURL} target="_blank">
          documentation here.
        </a>
      </span>
    </AnnouncementBanner>
  );
});
