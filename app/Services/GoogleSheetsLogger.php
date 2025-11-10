<?php

namespace App\Services;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;

class GoogleSheetsLogger
{
    protected Google_Service_Sheets $sheets;
    protected string $spreadsheetId;
    protected string $range;

    public function __construct()
    {
        $client = new Google_Client();
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $client->setAuthConfig(base_path(env('GOOGLE_SHEETS_CREDENTIALS')));

        $this->sheets       = new Google_Service_Sheets($client);
        $this->spreadsheetId = env('GOOGLE_SHEETS_SPREADSHEET_ID');
        $this->range         = env('GOOGLE_SHEETS_LOG_RANGE', 'TrackCampaign!A:F');
    }

    /**
     * Append a row of values to the sheet.
     *
     * @param array $row Simple array of values matching your columns A–F.
     */
    public function appendRow(array $row): void
    {
        $body = new Google_Service_Sheets_ValueRange([
            'values' => [ $row ],
        ]);

        $params = ['valueInputOption' => 'USER_ENTERED'];

        $this->sheets
             ->spreadsheets_values
             ->append(
                 $this->spreadsheetId,
                 $this->range,
                 $body,
                 $params
             );
    }
}
