package com.test.temporal.request;


import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

@Data
@Builder
@AllArgsConstructor
@NoArgsConstructor

public class CronRequest {

    private String cron_schedule;


    private String exec_time;

    private String time_out;
}
