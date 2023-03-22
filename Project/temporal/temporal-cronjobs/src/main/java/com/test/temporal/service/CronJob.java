package com.test.temporal.service;

public interface CronJob {

   // public void doSimpleJob();

    public void doSimpleJob(String cronSchedule,String executionTime, String timeout,String command,String taskQueue,String workflowId) ;
}
