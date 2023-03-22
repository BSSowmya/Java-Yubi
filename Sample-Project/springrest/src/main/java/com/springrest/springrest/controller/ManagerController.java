package com.springrest.springrest.controller;

import com.springrest.springrest.entities.Manager;
import com.springrest.springrest.services.ManagerService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import java.util.List;
@RestController
public class ManagerController {
    @Autowired
    private ManagerService managerService;



    @GetMapping("/managers")
        public List<Manager> getManagers(){
        return this.managerService.getManagers();
//        return "this is manager page";
    }
    @PostMapping("/managers")
    public Manager addManager(@RequestBody Manager manager){

        return this.managerService.addManager(manager);
    }

}
