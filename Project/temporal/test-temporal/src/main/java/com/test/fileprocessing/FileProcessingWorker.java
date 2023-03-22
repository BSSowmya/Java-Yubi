package com.test.fileprocessing;

import io.temporal.client.WorkflowClient;
import io.temporal.serviceclient.WorkflowServiceStubs;
import io.temporal.worker.Worker;
import io.temporal.worker.WorkerFactory;
import java.lang.management.ManagementFactory;
public class FileProcessingWorker {
    static final String TASK_QUEUE = "FileProcessing";

    public static void main(String[] args) {

        String hostSpecifiTaskQueue = ManagementFactory.getRuntimeMXBean().getName();

        // gRPC stubs wrapper that talks to the local docker instance of temporal service.
        WorkflowServiceStubs service = WorkflowServiceStubs.newLocalServiceStubs();
        // client that can be used to start and signal workflows
        WorkflowClient client = WorkflowClient.newInstance(service);

        // worker factory that can be used to create workers for specific task queues
        WorkerFactory factory = WorkerFactory.newInstance(client);
        // Worker that listens on a task queue and hosts both workflow and activity implementations.
        final Worker workerForCommonTaskQueue = factory.newWorker(TASK_QUEUE);
        workerForCommonTaskQueue.registerWorkflowImplementationTypes(FileProcessingWorkFlowImpl.class);
        StoreActivitiesImpl storeActivityImpl = new StoreActivitiesImpl(hostSpecifiTaskQueue);
        workerForCommonTaskQueue.registerActivitiesImplementations(storeActivityImpl);

        // Get worker to poll the host-specific task queue.
        final Worker workerForHostSpecificTaskQueue = factory.newWorker(hostSpecifiTaskQueue);
        workerForHostSpecificTaskQueue.registerActivitiesImplementations(storeActivityImpl);

        // Start all workers created by this factory.
        factory.start();
        System.out.println("Worker started for task queue: " + TASK_QUEUE);
        System.out.println("Worker Started for activity task Queue: " + hostSpecifiTaskQueue);
    }
}
