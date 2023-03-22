package com.springrest.springrest.services;

import java.util.List;

import com.springrest.springrest.entities.Employee;

public interface EmployeeService {

	
	public List<Employee> getEmployees();
	public Employee getEmployee(long id);
	
	public Employee addEmployee(Employee emp);
	
	public Employee updateEmployee(Employee emp);
	
	public void deleteEmployee(long id);
		

	
}
