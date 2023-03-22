package com.test.temporal.controller;

import com.test.temporal.service.CronJob;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.*;


@Controller
public class CronController {
    @Autowired
    CronJob cronJob;
    @GetMapping("/index")
    public String index() {
        return "index";
    }
    @PostMapping("/jobs")
    public String doSimpleJob(@RequestParam("cron_schedule") String cron_schedule,
                              @RequestParam("exec_time") String exec_time,
                              @RequestParam("time_out") String time_out,
                              @RequestParam("command") String command,
                              @RequestParam("wrkId") String workflowID){
//        System.out.println(cronRequest.getCron_schedule());
//        System.out.println(cron_schedule);
//        System.out.println(exec_time);
        System.out.println(workflowID);
        String taskQueue = "SampleCronTaskQueue - " + workflowID;
        String workFlowId = "SampleCronWorkflow - " + cron_schedule;
        cronJob.doSimpleJob(cron_schedule,exec_time,time_out,command,taskQueue,workflowID);
        return "job";

    }


}
