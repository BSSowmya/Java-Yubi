package com.springrest.springrest.services;


import com.springrest.springrest.entities.Manager;

import java.util.List;

public interface ManagerService {

    public List<Manager> getManagers();

    public  Manager addManager(Manager manager);
}
