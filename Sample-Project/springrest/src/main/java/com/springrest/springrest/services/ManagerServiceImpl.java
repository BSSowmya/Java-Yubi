package com.springrest.springrest.services;

import com.springrest.springrest.dao.ManagerDao;
import com.springrest.springrest.entities.Manager;
import io.temporal.activity.ActivityInterface;
import io.temporal.workflow.QueryMethod;
import io.temporal.workflow.WorkflowInterface;
import io.temporal.workflow.WorkflowMethod;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.util.List;

@ActivityInterface

@WorkflowInterface
@Service
public class ManagerServiceImpl implements  ManagerService{

    @Autowired
    private ManagerDao managerDao;
//    EmployeeService employeeService = ActivityStubUtils.getActivitiesStub();

    @QueryMethod
    @Override
    public List<Manager> getManagers() {
        return this.managerDao.findAll();
    }

    @WorkflowMethod
    @Override
    public Manager addManager(Manager manager) {
        managerDao.save(manager);
        return manager;
    }
}
