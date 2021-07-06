import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { DocLink } from 'merchant/components/DocsLink';

export default React.memo(({ children, docURL }) => {
  return (
    <AnnouncementBanner title="Important Announcement!" theme="warning">
      <span class="display-inline">
        {children}{' '}
        <DocLink class="btn-link" href={docURL} target="_blank">
          documentation here.
        </DocLink>
      </span>
    </AnnouncementBanner>
  );
});
