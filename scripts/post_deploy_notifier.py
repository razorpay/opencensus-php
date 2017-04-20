#!/usr/bin/python
import json
import requests
import os
import sys
from datetime import datetime
import re


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
        #return KeyStore.get_key("GITHUB_TOKEN")
        return KeyStore.get_key("github_token")

    @staticmethod
    def get_wercker_api_token():
        return KeyStore.get_key("WERCKER_API_TOKEN")

    @staticmethod
    def get_prod_pipeline_id():
        return KeyStore.get_key("RZP_WERCKER_PROD_PIPELINE_ID")

    @staticmethod
    def get_slack_token():
        return KeyStore.get_key('SLACK_TOKEN')


class SlackNotifier:
    def __init__(self):
        # currently hardcoding this. We need to arrive at this value later
        self.channel = "C0KHQBRJN"
        self.icon_url = 'https://s3-us-west-2.amazonaws.com/slack-files2/bot_icons/2015-06-25/6837962368_48.png'
        self.url = 'https://razorpay.slack.com/services/hooks/incoming-webhook?token=%s' % (
            KeyStore.get_slack_token())
        self.username = 'wercker'

    def formatSlackMessage(self, message):
        attachments = {}
        slackMessage = {}
        metadata = message['metadata']
        startTime = "%s UTC" %(metadata['startedAt'].strftime('%d-%b-%Y %H:%M:%S'))
        finishedAt = "%s UTC" %(metadata['finishedAt'].strftime('%d-%b-%Y %H:%M:%S'))
        attachments['title'] = 'Deployed by %s' % (metadata['deployed_by'])
        attachments['fallback'] = 'API Deploy Completed at :%s' % (
            metadata['finishedAt'])
        attachments['author'] = self.username
        attachments['mrkdwn_in'] = ['text', 'fields']
        fields = []
        for commit in message['commits']:
            tmp = {
                'short': True,
                'title': 'PR:#%s' % (commit['pr']),
                'value': '%s\n%s' % (commit['title'], commit['pr_url'])
            }
            fields.append(tmp)
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
        self.base_url = 'https://api.github.com/repos/razorpay/api/pulls/'
        self.base_pr_url = 'https://github.com/razorpay/api/pull/'

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


class MergeCommitParser:

    def __init__(self):
        self.base_url = "https://app.wercker.com/api/v3/runs"
        self.limit = 2  # get the most recent deploys alone
        self.branch = "master"
        self.result = "passed"
        self.status = "finished"
        self.github_processor = GitProcessor()
        self.base_pr_url = self.github_processor.base_pr_url

    def get_pipeline_runs(self):
        wercker_api_token = KeyStore.get_wercker_api_token()
        headers = {'Authorization': 'Bearer {0}'.format(wercker_api_token)}
        pipeline_id = KeyStore.get_prod_pipeline_id()
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
            print "Exception making request:%s" % (e)
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

    def parseMergeCommit(self):
        runs = self.get_pipeline_runs()
        metadata = {}
        commits = []
        if len(runs) > 0:
            firstRun = runs[0]
            metadata = {
                'startedAt': datetime.strptime(firstRun['startedAt'], '%Y-%m-%dT%H:%M:%S.%fZ'),
                'finishedAt': datetime.strptime(firstRun['finishedAt'], '%Y-%m-%dT%H:%M:%S.%fZ'),
                'deployed_by': firstRun['user']['name'],
            }
        for run in runs:
            # todo: exclude current run
            msg = run['message']
            pr_nums = self.findMergedPrs(msg)
            if len(pr_nums) > 0:
                message = {
                    'commitHash': run['commitHash'],
                    'pr_nums': pr_nums
                }
                commits.append(message)
        return {'metadata': metadata, 'commits': commits}

    def getDeployDetails(self):
        messages = self.parseMergeCommit()
        parsed_messages = {'metadata': messages['metadata']}
        commits = []
        for m in messages['commits']:
            pr_nums = m['pr_nums']
            pr_details = {}
            for p in pr_nums:
                title, body = self.github_processor.process_pr_details(p)
                pr_details['title'] = title
                pr_details['details'] = body
                pr_details['pr'] = p
                pr_details['pr_url'] = '%s%s' %(self.base_pr_url, p)
            del m['pr_nums']
            m.update(pr_details)
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
