import os
import requests
import re

github_token = os.getenv('GITHUB_TOKEN')
pr_number = os.getenv('PR_NUMBER')
headers = {'Authorization': f'Bearer {github_token}'}

def post_comment(routes_added_api):
    headers_for_post_comment = {
        'Authorization': f'token {github_token}',
        'Accept': 'application/vnd.github.v3+json'
    }

    api_url = f'https://api.github.com/repos/razorpay/api/issues/{pr_number}/comments'

    comment_body = f'''
:warning: It looks your PR has introduced a new route. Add the owner service for this route to help teams reach to you quicker on issues/during outages for this route. Routes Added
                        `{routes_added_api}`

:arrow_right: Raise [inventory manifest](https://github.com/razorpay/inventory-manifests) PR(sample [PR](https://github.com/razorpay/inventory-manifests/pull/161/files)) in `resources/route.yaml`
:arrow_right: Merge your inventory manifest PR
:arrow_right: Paste link of inventory-manifest PR in description of this API PR.

:information_source: Check CI output for `Check Resource Owner Mapping` CI Action for more info on what is happening
:information_source: Reach out to @razorpay/techpaymentscore or @razorpay/techcommonplatforms for help
    '''

    comment_data = {
        'body': comment_body
    }

    response = requests.post(api_url, headers=headers_for_post_comment, json=comment_data)

    if response.status_code == 201:
        print('Comment posted successfully.')
    else:
        print(f'Failed to post comment. Status code: {response.status_code}, Response: {response.text}')

def get_pr_details(owner, repo, pr_number):

    # Get the PR information to extract the base and head branches
    pr_url = f'https://api.github.com/repos/{owner}/{repo}/pulls/{pr_number}'

    pr_response = requests.get(pr_url, headers=headers)

    if pr_response.status_code != 200:
        print(f"Error fetching PR details: {pr_response.text}\n{pr_url}")
        exit(1)

    return pr_response.json()

def get_pr_diff(owner, repo, pr_number, pr_data):
    base_branch = pr_data['base']['ref']
    head_branch = pr_data['head']['ref']

    # Get the diff using the compare API
    compare_url = f'https://api.github.com/repos/{owner}/{repo}/compare/{base_branch}...{head_branch}'
    compare_response = requests.get(compare_url, headers=headers)
    if compare_response.status_code == 200:
        diff_content = compare_response.json().get('files', [])
        return diff_content
    else:
        print(f"Error fetching PR diff: {compare_response.status_code}")
        exit(1)

def get_diffs(pr_diff, files_to_check, regex):
    x = []
    if pr_diff is not None:
        for file_diff in pr_diff:
            if len(files_to_check) == 0 or file_diff['filename'] in files_to_check:
                x += re.findall(regex , file_diff['patch'])
    return x

if __name__ == "__main__":

    print("fetching api pr details")
    pr_details = get_pr_details('razorpay', 'api', pr_number)

    label_to_skip = 'skip-inventory-manifest-check'
    if any(label_data['name'] == label_to_skip for label_data in pr_details.get('labels', [])):
        print("label to skip found checking for approval")
        exit(0)

    print("fetching pr diff for api")
    pr_diff = get_pr_diff('razorpay', 'api', pr_number, pr_details)

    files_to_check_route_changes = ["app/Http/Route.php"]
    regex_to_get_route_changes = "\+[^\'\n]+\'([^\']+)\'[^=]+=>[^\[][^,]+,[^,]+,[^\]]+]"
    routes_added_api = get_diffs(pr_diff, files_to_check_route_changes, regex_to_get_route_changes)
    print("routes added are : ", routes_added_api)

    if len(routes_added_api) == 0:
        print("no new routes added")
        exit(0)

    post_comment(routes_added_api)
    if pr_details['body'] == None:
        print("inventory manifest link not found body empty")
        exit(1)

    search_group = re.findall("https://github\.com/razorpay/inventory-manifests/pull/([0-9]+)",pr_details['body'])
    if len(search_group) >= 1:
        print("inventory manifest pr link found")
        exit(0)
    if len(search_group) == 0:
        print("no inventory manifest pr link found")
        exit(1)
