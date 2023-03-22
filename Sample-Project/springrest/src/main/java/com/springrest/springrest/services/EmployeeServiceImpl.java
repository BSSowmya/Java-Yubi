package com.springrest.springrest.services;

import java.util.ArrayList;
import java.util.List;
import java.util.stream.Collectors;


import io.temporal.activity.ActivityInterface;
import io.temporal.workflow.QueryMethod;
import io.temporal.workflow.WorkflowInterface;
import io.temporal.workflow.WorkflowMethod;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import com.springrest.springrest.dao.EmployeeDao;
import com.springrest.springrest.entities.Employee;




@Service
public class EmployeeServiceImpl implements EmployeeService {
	
	@Autowired
	private EmployeeDao employeeDao;


//	List<Employee> list;
	
	public EmployeeServiceImpl() {
//		list= new ArrayList<>();
//		list.add(new Employee(1,"Sowmya","Backend developer"));
//		list.add(new Employee(2,"Navya","Frontend developer"));
		
		
	}
	
@Override	
public List<Employee> getEmployees() {
		// TODO Auto-generated method stub
//		return list;
	return employeeDao.findAll();
	}

@Override	
public Employee getEmployee(long id) {
	// TODO Auto-generated method stub
//	Employee emp = null;
//			
//	for (Employee e :list)		{
//		 if (e.getId()== id) {
//			 emp = e;
//			 break;
//		 }
//	}
	return employeeDao.getById(id);
}


@Override
public Employee addEmployee(Employee emp) {
	// TODO Auto-generated method stub
//	list.add(emp);
	employeeDao.save(emp);
	
	return emp;
}

@Override
public Employee updateEmployee(Employee emp) {
	// TODO Auto-generated method stub
	
//	list.forEach( e->{
//		if(e.getId()== emp.getId()) {
//			e.setName(emp.getName());
//			e.setDescription(emp.getDescription());
//			
//		}
//		
//		
//	});
//	
//	for (Employee e:list) {
//		if (e.getId()==emp.getId()) {
//			
//			System.out.println(emp.getId());
//			System.out.println(emp.getName());
//			int id = (int)emp.getId() -1; 
//		
//			list.set(id, emp);
//			break;
//			
//		}
//		
//	}
	
	employeeDao.save(emp);
	
	return emp;
}

@Override
public void deleteEmployee(long id) {
	// TODO Auto-generated method stub
//	for (Employee e:list) {
//		if (e.getId()== id) {
//		
//			int empId = (int)id -1; 
//			emp =e;
//			list.remove(empId);
//			break;
//			
//		}
//		
//	}
//	
	
//	list = list.stream().filter(emp->emp.getId()!=id).collect(Collectors.toList());
	Employee emp =employeeDao.getOne(id);
	employeeDao.delete(emp);
	
	
}

}
