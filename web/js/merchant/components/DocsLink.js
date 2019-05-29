import ShowWhen from './ShowWhen';

export default function DocsLink({ url, title = 'Documentation' }) {
  return (
    <ShowWhen
      additionalCondition={user =>
        user.isOrgAllowedFunctionality('external_links')
      }
    >
      <a class="btn btn-link" href={url} target="_blank">
        {`${title}`} &nbsp;
        <i class="i i-external-link" />
      </a>
    </ShowWhen>
  );
}
