#!/usr/bin/python
import json
import requests
import os
import sys
from datetime import datetime
import re

# TODO: Slack notification pending


class SlackNotifier:

    def __init__(self, message, channel):
        self.message = message
        self.channel = channel

    def notify():
        pass


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
        return KeyStore.get_key("GITHUB_TOKEN")

    @staticmethod
    def get_wercker_api_token():
        return KeyStore.get_key("WERCKER_API_TOKEN")

    @staticmethod
    def get_prod_pipeline_id():
        return KeyStore.get_key("PROD_PIPELINE_ID")


class GitProcessor:

    def __init__(self):
        self.github_token = KeyStore.get_github_token()
        self.base_url = 'https://api.github.com/repos/razorpay/api/pulls/'

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
        messages = []
        for run in runs:
            # todo: exclude current run
            msg = run['message']
            pr_nums = self.findMergedPrs(msg)
            if len(pr_nums) > 0:
                message = {
                    'startedAt': datetime.strptime(run['startedAt'], '%Y-%m-%dT%H:%M:%S.%fZ'),
                    'finishedAt': datetime.strptime(run['finishedAt'], '%Y-%m-%dT%H:%M:%S.%fZ'),
                    'commitHash': run['commitHash'],
                    'deployed_by': run['user']['name'],
                    'pr_nums': pr_nums
                }
                messages.append(message)
        return messages

    def getDeployDetails(self):
        messages = self.parseMergeCommit()
        parsed_messages = []
        for m in messages:
            pr_nums = m['pr_nums']
            pr_details = {}
            for p in pr_nums:
                title, body = self.github_processor.process_pr_details(p)
                pr_details['title'] = title
                pr_details['details'] = body
            del m['pr_nums']
            m.update(pr_details)
            parsed_messages.append(messages)
        return parsed_messages


if __name__ == "__main__":
    parser = MergeCommitParser()
    deploy_details = parser.getDeployDetails()
    print deploy_details
