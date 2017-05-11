#!/usr/bin/python
import json
import requests
import os
import sys
from datetime import datetime
import re


WERCKER_USERNAME="wercker"

class KeyStore:
    @staticmethod
    def get_key(key):
        err = "[ERROR] Missing key from environment - {0}".format(key)
        try:
            return os.environ["{0}".format(key)]
        except Exception, e:
            print err
            sys.exit(1)

    @staticmethod
    def get_github_token():
        return KeyStore.get_key("github_token")

    @staticmethod
    def get_wercker_api_token():
        return KeyStore.get_key("WERCKER_API_TOKEN")

    @staticmethod
    def get_prod_pipeline_id():
        return KeyStore.get_key("RZP_WERCKER_PROD_PIPELINE_ID")

    @staticmethod
    def get_current_pipeline_id():
        return KeyStore.get_key("RZP_WERCKER_CURR_PIPELINE_ID")

    @staticmethod
    def get_slack_token():
        return KeyStore.get_key('SLACK_TOKEN')

    @staticmethod
    def get_slack_deploy_channel_id():
        return KeyStore.get_key('SLACK_DEPLOY_CHANNEL_ID')

    @staticmethod
    def get_wercker_icon_url():
        return KeyStore.get_key('WERCKER_ICON_URL')

    @staticmethod
    def get_slack_base_url():
        return KeyStore.get_key('SLACK_BASE_URL')


class SlackNotifier:
    def __init__(self):
        global WERCKER_USERNAME
        # currently hardcoding this. We need to arrive at this value later
        self.channel = KeyStore.get_slack_deploy_channel_id()
        self.icon_url = KeyStore.get_wercker_icon_url()
        self.base_slack_url = KeyStore.get_slack_base_url()
        self.url = '%s/services/hooks/incoming-webhook?token=%s' % (
            self.base_slack_url, KeyStore.get_slack_token())
        self.username = WERCKER_USERNAME

    def formatSlackMessage(self, message):
        attachments = {}
        slackMessage = {}
        metadata = message['metadata']
        startTime = "%s UTC" % (
            metadata['startedAt'].strftime('%d-%b-%Y %H:%M:%S'))
        finishedAt = "%s UTC" % (
            metadata['finishedAt'].strftime('%d-%b-%Y %H:%M:%S'))
        attachments['title'] = 'Deployed by %s' % (metadata['deployed_by'])
        attachments['fallback'] = 'API Deploy Completed at :%s' % (
            metadata['finishedAt'])
        attachments['author'] = self.username
        attachments['mrkdwn_in'] = ['text', 'fields']
        fields = []
        for commit in message['commits']:
            tmp = {'short': True}
            if 'pr' in commit:
                tmp['title'] = 'PR:#%s' % (commit['pr'])
                tmp['value'] = '%s\n%s' % (commit['title'], commit['pr_url'])
            else:
                tmp['title'] = commit['title']
                tmp['value'] = commit['url']
            fields.append(tmp)
        # the below hack is only for formatting. Basically, check if the number
        # of items is even, else append a dummy one so the metadata gets
        # to the next line
        if len(fields) % 2 != 0:
            fields.append({'short': True, 'title': '', 'value': ''})
        fields.append(
            {'short': True, 'title': 'Deploy Started', 'value': startTime})
        fields.append(
            {'short': True, 'title': 'Deploy Ended', 'value': finishedAt})
        attachments['fields'] = fields
        slackMessage['attachments'] = [attachments]
        slackMessage['username'] = self.username
        slackMessage['icon_url'] = self.icon_url
        slackMessage['channel'] = self.channel
        slackMessage['as_user'] = False
        return slackMessage

    def notify(self, message):
        slackMessage = self.formatSlackMessage(message)
        try:
            r = requests.post(self.url, data=json.dumps(slackMessage))
            r.raise_for_status()
            # TODO: check response status
        except Exception, e:
            print "Slack Post Failed. Exception:%s, message:%s" % (e, slackMessage)
            sys.exit(1)
        print "Slack Post Complete"


class GitProcessor:

    def __init__(self):
        self.github_token = KeyStore.get_github_token()
        self.base_api_url = 'https://github.com/razorpay/api'
        self.base_url = 'https://api.github.com/repos/razorpay/api/pulls/'
        self.base_pr_url = "%s/pull/" % (self.base_api_url)

    def process_pr_details(self, pr):
        url = "%s%s" % (self.base_url, pr)
        try:
            response = requests.get(url, auth=('user', self.github_token))
            # TODO: check for the response status code
            resp_json = response.json()
            title = resp_json['title']
            body = resp_json['body']
            return [title, body]
        except Exception, e:
            print "Exception fetching github details for commit : %s, url:%s, Exception:%s" % (pr, url, e)
            sys.exit(0)

    def process_commit_details(self, commitHash):
        return "%s/commit/%s" % (self.base_api_url, commitHash)


class MergeCommitParser:

    def __init__(self):
        self.base_url = "https://app.wercker.com/api/v3/runs"
        self.limit = 1  # get the most recent deploys alone
        self.branch = "master"
        self.result = "passed"
        self.status = "finished"
        self.github_processor = GitProcessor()
        self.base_pr_url = self.github_processor.base_pr_url

    def get_pipeline_runs(self):
        wercker_api_token = KeyStore.get_wercker_api_token()
        headers = {'Authorization': 'Bearer {0}'.format(wercker_api_token)}
        pipeline_id = KeyStore.get_prod_pipeline_id()
        current_pipeline_id = KeyStore.get_current_pipeline_id()
        if current_pipeline_id != pipeline_id:
            sys.exit(0)
        params = {
            'pipelineId': pipeline_id,
            'limit': self.limit,
            'branch': self.branch,
            'result': self.result,
            'status': self.status
        }
        try:
            response = requests.get(
                self.base_url, headers=headers, params=params, verify=True)
            return response.json()
        except Exception, e:
            print "Exception making pipeline_run request:%s, Exception:%s" % (self.base_url, e)
            sys.exit(1)

    def findMergedPrs(self, msg):
        messages = msg.split('\n')
        pr_nums = []
        for m in messages:
            try:
                m = m.strip('\n')
                prs = re.findall(r'#\d+', m)
                if len(prs) > 0:
                    pr_nums.append(prs[0].replace('#', ''))
            except Exception, e:
                print "PR Parse Error:%s, msg:%s" % (e, m)
                sys.exit(0)
        return pr_nums

    def parseDeployCommits(self, run_url, commitHash):
        wercker_api_token = KeyStore.get_wercker_api_token()
        headers = {'Authorization': 'Bearer %s' % (wercker_api_token)}
        commit_pr_nums = []
        commit_messages = []
        try:
            response = requests.get(run_url, headers=headers, verify=True)
            data = response.json()
            commits = data.get('commits', [])
            for commit in commits:
                pr_nums = self.findMergedPrs(commit['message'])
                if len(pr_nums) > 0:
                    commit_pr_nums.extend(pr_nums)
                else:
                    commit_hash = commit['commit']
                    if commit_hash != commitHash:
                        tmp = {
                            'commitHash': commit['commit'],
                            'message': commit['message']
                        }
                        commit_messages.append(tmp)
        except Exception, e:
            print "Exception Fetching Run url:%s, Exception:%s" % (run_url, e)
        return [commit_pr_nums, commit_messages]

    def parseMergeCommit(self):
        runs = self.get_pipeline_runs()
        metadata = {}
        commits = []
        firstRun = None
        if len(runs) > 0:
            firstRun = runs[0]
            if firstRun['result'] != 'passed':
                print "Deploy Failed. Exiting"
                sys.exit(0)
            metadata = {
                'startedAt': datetime.strptime(firstRun['startedAt'], '%Y-%m-%dT%H:%M:%S.%fZ'),
                'finishedAt': datetime.strptime(firstRun['finishedAt'], '%Y-%m-%dT%H:%M:%S.%fZ'),
                'deployed_by': firstRun['user']['name'],
            }

        if firstRun != None:
            msg = firstRun['message']
            pr_nums = self.findMergedPrs(msg)
            run_url = str(firstRun['url'])
            commit_prs, message_hashes = self.parseDeployCommits(
                run_url, firstRun['commitHash'])
            pr_nums.extend(commit_prs)
            pr_nums = list(set(pr_nums))
            if len(pr_nums) > 0:
                message = {
                    'commitHash': firstRun['commitHash'],
                    'pr_nums': pr_nums
                }
                commits.append(message)
            else:
                message = {
                    'commitHash': firstRun['commitHash'],
                    'message': firstRun['message']
                }
                commits.append(message)
            if len(message_hashes) > 0:
                commits.extend(message_hashes)
        return {'metadata': metadata, 'commits': commits}

    def getDeployDetails(self):
        messages = self.parseMergeCommit()
        parsed_messages = {'metadata': messages['metadata']}
        commits = []
        for m in messages['commits']:
            pr_nums = m.get('pr_nums', None)
            if pr_nums:
                pr_details = {}
                for p in pr_nums:
                    title, body = self.github_processor.process_pr_details(p)
                    pr_details['title'] = title
                    pr_details['details'] = body
                    pr_details['pr'] = p
                    pr_details['pr_url'] = '%s%s' % (self.base_pr_url, p)
                del m['pr_nums']
                m.update(pr_details)
            else:
                message = m['message']
                commit_details = {}
                commit_details['title'] = message
                commit_details['url'] = self.github_processor.process_commit_details(
                    m['commitHash'])
                del m['commitHash']
                del m['message']
                m.update(commit_details)
            commits.append(m)
        parsed_messages['commits'] = commits
        return parsed_messages

    def getDeployDetailsAndNotifySlack(self):
        deployDetails = self.getDeployDetails()
        slackNotifier = SlackNotifier()
        slackNotifier.notify(deployDetails)


if __name__ == "__main__":
    parser = MergeCommitParser()
    parser.getDeployDetailsAndNotifySlack()
