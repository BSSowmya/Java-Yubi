package com.springrest.springrest.dao;

import org.springframework.data.jpa.repository.JpaRepository;

import com.springrest.springrest.entities.Employee;

public interface EmployeeDao  extends JpaRepository<Employee,Long>{

}
