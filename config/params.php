<?php
return [
    'adminEmail' => 'admin@example.com',

    "xmlPath" => Yii::getAlias("@server") . "/web/xml",
    "upXmlPath" => Yii::getAlias("@server") . "/web/upXml",
    "postUrl" => "192.168.1.123:8081/api/request",
    "reportUrl" => "192.168.1.123/monitor_api/inner/report.html",
    "encoding" => "UTF-8",
    "instructions" => [
        "channel_scan_query",
        "auto_analysis_time_query",
        "change_program_query",
        "stop_playing_video",
        "record_capability_query",
        "set_auto_record_channel",
        "video_history_inquiry",
        "channel_raw_stream_query",
        "video_history_down_inquiry",
        "get_record_video_time",
        "video_history_lost_inquiry",
        "alarm_time_set",
        "alarm_rf_threshold_set",
        "alarm_stream_threshold_set",
        "alarm_program_threshold_set",
        "alarm_rf_switch_set",
        "alarm_stream_threshold_set",
        "alarm_program_threshold_set",
        "agent_info_set",
        "osd_format_set",
        "rf_stream_info_query",
        "dev_status_query",
        "equipment_break_down_set"
    ],
    "report_instructions"=>[
        //"ChannelScanQuery",
        "ChannelScanNotice",
        "AlarmSearchRFSet",
        "AlarmSearchStreamSet",
        "AlarmSearchPSet",
    ]
];
