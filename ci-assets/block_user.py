import os
import json
import base64
import gspread
from oauth2client.service_account import ServiceAccountCredentials

def check_blocked_status(github_id):
    creds_base64 = os.environ['GOOGLE_SHEETS_CREDS']
    creds_json_str = base64.b64decode(creds_base64).decode('utf-8')
    creds_json = json.loads(creds_json_str)

    # Convert the dictionary to a credentials object
    scope = ["https://spreadsheets.google.com/feeds"]
    creds = ServiceAccountCredentials.from_json_keyfile_dict(creds_json, scope)

    client = gspread.authorize(creds)

    # Use the spreadsheet ID to open the spreadsheet
    spreadsheet_id = "1Nf5MURrNfvqEPD16v6-rC8_Rwn77DbABNwPXJtoW-p0"
    spreadsheet = client.open_by_key(spreadsheet_id)

    # Use the sheet ID (gid) to select the specific sheet
    sheet_id = 535303587
    sheet = [worksheet for worksheet in spreadsheet.worksheets() if worksheet.id == sheet_id][0]

    try:
        # Search for the GitHub ID in the sheet
        cell = sheet.find(github_id)
        if cell is None:
            isBlocked = "FALSE"
            print(f"::error:: GitHub ID not found for {github_id}. Setting isBlocked to False.")
        else:
            # Check the status in the corresponding row
            cell_value = sheet.cell(cell.row, 4).value
            isBlocked = cell_value.upper() if cell_value is not None else "FALSE"
    except gspread.exceptions.GSpreadException:
        isBlocked = "FALSE"
        print(f"::error:: GitHub ID not found for {github_id}. Setting isBlocked to False.")

    if isBlocked == "TRUE":
        print(f"::error::User {github_id} is blocked from merging!")
        exit(1)
    else:
        print(f"GitHub ID {github_id} is not blocked. Proceed with merging.")

if __name__ == "__main__":
    github_id = os.environ['GITHUB_ACTOR']
    check_blocked_status(github_id)
