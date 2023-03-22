package com.springrest.springrest.entities;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;
import lombok.ToString;

import java.util.List;
@Data
@AllArgsConstructor
@NoArgsConstructor
@ToString
@Entity

public class Manager {
@Id
@GeneratedValue(strategy = GenerationType.AUTO)
    private long mid;
    private String managerName;
    @OneToMany(targetEntity = Employee.class,cascade = CascadeType.ALL)
    @JoinColumn(name="mid_fk",referencedColumnName = "mid")
    private List<Employee> empList;
}
