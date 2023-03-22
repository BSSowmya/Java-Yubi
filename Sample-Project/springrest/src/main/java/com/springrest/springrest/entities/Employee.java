package com.springrest.springrest.entities;

import jakarta.persistence.Entity;
import jakarta.persistence.Id;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;
import lombok.ToString;

import java.util.Date;

@Data
@AllArgsConstructor
@NoArgsConstructor
@ToString
@Entity
public class Employee {

	@Id
	private long id;

	private String firstName;

	private String lastName;

	private String phno;

	private Date dob;

	private String description;

	public Employee(long id, String phno, String firstname,String lastname, String  description,Date dob) {
		super();
		this.id = id;
		this.phno = phno;
		this.firstName = firstname;
		this.lastName = lastname;
		this.description = description;
		this.dob =dob;
	}

}
