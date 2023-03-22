package com.test.fileprocessing;

import java.net.URL;
import io.temporal.activity.ActivityOptions;
import io.temporal.common.RetryOptions;
import io.temporal.workflow.Workflow;
import java.net.URL;
import java.time.Duration;
import java.util.Optional;

public class FileProcessingWorkFlowImpl  implements  FileProcessingWorkFlow{
    private final StoreActivities defaultTaskQueueActivities;

    public FileProcessingWorkFlowImpl() {
        // Create activity clients.
        ActivityOptions ao =
                ActivityOptions.newBuilder()
                        .setStartToCloseTimeout(Duration.ofSeconds(20))
                        .setRetryOptions(
                                RetryOptions.newBuilder()
                                        .setInitialInterval(Duration.ofSeconds(1))
                                        .setMaximumAttempts(4)
                                        .setDoNotRetry(IllegalArgumentException.class.getName())
                                        .build())
                        .build();
        this.defaultTaskQueueActivities = Workflow.newActivityStub(StoreActivities.class, ao);
    }

    @Override
    public void processFile(URL source, URL destination) {
        RetryOptions retryOptions =
                RetryOptions.newBuilder().setInitialInterval(Duration.ofSeconds(1)).build();
        // Retries the whole sequence on any failure, potentially on a different host.
        Workflow.retry(retryOptions, Optional.empty(), () -> processFileImpl(source, destination));
    }

    private void processFileImpl(URL source, URL destination) {
        StoreActivities.TaskQueueFileNamePair downloaded = defaultTaskQueueActivities.download(source);

        // Now initialize stubs that are specific to the returned task queue.
        ActivityOptions hostActivityOptions =
                ActivityOptions.newBuilder()
                        .setTaskQueue(downloaded.getHostTaskQueue())
                        // Set the amount a time an activity task can stay in the task queue before its picked
                        // up by a Worker. It allows us to support cases where
                        // the activity worker crashes or restarts before the activity starts execution.
                        // This timeout should be specified only when host specific activity task queues are
                        // used like in this sample.
                        // Note that scheduleToStart timeout is not retryable and retry options will ignore it.
                        // This timeout has to be handled by Workflow code.
                        .setScheduleToStartTimeout(Duration.ofSeconds(10))
                        // Set the max time of a single activity execution attempt.
                        // Activity is going to be executed by a Worker listening to the specified
                        // host task queue. If the activity is started but then the activity worker crashes
                        // for some reason, we want to make sure that it is retried after the specified timeout.
                        // This timeout should be be as short as the longest possible execution of the Activity.
                        .setStartToCloseTimeout(Duration.ofSeconds(2))
                        .setRetryOptions(
                                RetryOptions.newBuilder()
                                        .setInitialInterval(Duration.ofSeconds(1))
                                        .setMaximumAttempts(4)
                                        .setDoNotRetry(IllegalArgumentException.class.getName())
                                        .build())
                        .build();
        StoreActivities hostSpecificStore =
                Workflow.newActivityStub(StoreActivities.class, hostActivityOptions);

        // Call processFile activity to zip the file.
        // Call the activity to process the file using worker-specific task queue.
        String processed = hostSpecificStore.process(downloaded.getFileName());
        // Call upload activity to upload the zipped file.
        hostSpecificStore.upload(processed, destination);
    }
}
