package com.springrest.springrest.controller;

import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestMethod;
import org.springframework.web.bind.annotation.RestController;

import com.springrest.springrest.entities.Employee;
import com.springrest.springrest.services.EmployeeService;

@RestController
public class MyController {
	
	
	@Autowired
	private EmployeeService employeeService;
	
	@GetMapping("/home")
	public String home() {
		return "This is home page";
	}
@GetMapping("/employees")
	public List<Employee> getEmployees(){
		
	return this.employeeService.getEmployees();
		
		
	}
	
@GetMapping("/employees/{empId}")
	public Employee getEmployee(@PathVariable String empId) {
		return this.employeeService.getEmployee(Long.parseLong(empId));
		
		
	}

@PostMapping("/employees")
public Employee addEmployee(@RequestBody Employee emp) {
	return this.employeeService.addEmployee(emp);
	
	
}

@PutMapping("employees")
public Employee updateEmployee(@RequestBody Employee emp) {
	
	return this.employeeService.updateEmployee(emp);
}

@RequestMapping(path="/employees/{empId}",method=RequestMethod.DELETE)
public ResponseEntity<HttpStatus> deleteEmployee(@PathVariable String empId) {
	try {
	 this.employeeService.deleteEmployee(Long.parseLong(empId));
	 
	 return new ResponseEntity<>(HttpStatus.OK);
		
	}catch (Exception e) {
		
		 return new ResponseEntity<>(HttpStatus.BAD_REQUEST);
	}

}

}
